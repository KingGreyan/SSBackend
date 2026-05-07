<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Category;

class AnalysisController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $range = $request->get('range', 'monthly'); // weekly, monthly, yearly
        $refDate = $request->has('date') ? Carbon::parse($request->get('date')) : Carbon::now();

        // 1. Determine Date Range
        if ($range === 'weekly') {
            $startDate = $refDate->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $refDate->copy()->endOfWeek(Carbon::SUNDAY);
            $groupByFormat = '%Y-%m-%d'; // MySQL Format
            $phpDateFormat = 'Y-m-d';    // PHP Format
            $labelFormat = 'D'; // Mon, Tue...
        } elseif ($range === 'monthly') {
            $startDate = $refDate->copy()->startOfMonth();
            $endDate = $refDate->copy()->endOfMonth();
            $groupByFormat = '%Y-%m-%d'; // MySQL Format
            $phpDateFormat = 'Y-m-d';    // PHP Format
            $labelFormat = 'j'; // 1, 2, 3...
        } elseif ($range === 'yearly') {
            $startDate = $refDate->copy()->startOfYear();
            $endDate = $refDate->copy()->endOfYear();
            $groupByFormat = '%Y-%m';    // MySQL Format
            $phpDateFormat = 'Y-m';      // PHP Format
            $labelFormat = 'M'; // Jan, Feb...
        } else {
            return response()->json(['error' => 'Invalid range'], 400);
        }

        // 2. Base Query
        $query = $user->transactions()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);

        // 3. Summary Stats
        $income = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        // 4. Category Breakdown (Expense)
        $expenseByCategory = (clone $query)
            ->where('type', 'expense')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get()
            ->map(function ($item) use ($expense) {
                return [
                    'name' => $item->category ? $item->category->name : 'Uncategorized',
                    'color' => $item->category ? $item->category->color : '#94a3b8',
                    'total' => (float) $item->total,
                    'percentage' => $expense > 0 ? round(($item->total / $expense) * 100, 1) : 0,
                    'icon' => $item->category ? $item->category->icon : 'category',
                ];
            });

        // 5. Category Breakdown (Income)
        $incomeByCategory = (clone $query)
            ->where('type', 'income')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->category ? $item->category->name : 'Uncategorized',
                    'color' => $item->category ? $item->category->color : '#94a3b8',
                    'total' => (float) $item->total,
                    'icon' => $item->category ? $item->category->icon : 'attach-money',
                ];
            });

        // 6. Trend Data (Daily/Monthly)
        $trendData = [];
        $period = $range === 'yearly' 
            ? \Carbon\CarbonPeriod::create($startDate, '1 month', $endDate)
            : \Carbon\CarbonPeriod::create($startDate, '1 day', $endDate);

        $dateGroups = $user->transactions()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(
                DB::raw("DATE_FORMAT(date, '{$groupByFormat}') as group_date"),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            )
            ->groupBy('group_date')
            ->get()
            ->keyBy('group_date');

        foreach ($period as $date) {
            $key = $date->format($phpDateFormat);
            $record = $dateGroups->get($key);
            
            $trendData[] = [
                'label' => $date->format($labelFormat),
                'fullDate' => $key,
                'income' => $record ? (float) $record->income : 0,
                'expense' => $record ? (float) $record->expense : 0,
            ];
        }

        // 7. Insights
        $highestExpenseDay = (clone $query)
            ->where('type', 'expense')
            ->select('date', DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderByDesc('total')
            ->first();

        // 8. Weekly Pattern (Expense by Day of Week)
        $weeklyPatternRaw = (clone $query)
            ->where('type', 'expense')
            ->select(DB::raw('DAYOFWEEK(date) as day_num'), DB::raw('SUM(amount) as total'))
            ->groupBy('day_num')
            ->get()
            ->pluck('total', 'day_num');

        // Map 1=Sun, 2=Mon ... (MySQL standard: 1=Sunday)
        $weekMap = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat', 1 => 'Sun'];
        $weeklyPattern = [];
        foreach ($weekMap as $num => $label) {
            $weeklyPattern[] = [
                'day' => $label,
                'amount' => (float) ($weeklyPatternRaw[$num] ?? 0)
            ];
        }

        // 9. Most Frequent Category
        $mostFrequentCategory = (clone $query)
            ->where('type', 'expense')
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->with('category')
            ->first();

        return response()->json([
            'range' => $range,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $balance,
                'avgDailySpend' => $expense / ($endDate->diffInDays($startDate) + 1),
                'savingsRate' => $income > 0 ? round((($income - $expense) / $income) * 100, 1) : 0,
            ],
            'categories' => [
                'expense' => $expenseByCategory,
                'income' => $incomeByCategory,
            ],
            'trend' => $trendData,
            'weeklyPattern' => $weeklyPattern,
            'insights' => [
                'highestExpenseDay' => $highestExpenseDay ? [
                    'date' => $highestExpenseDay->date,
                    'amount' => (float) $highestExpenseDay->total
                ] : null,
                'topExpenseCategory' => $expenseByCategory->first() ?? null,
                'mostFrequentCategory' => $mostFrequentCategory ? [
                    'name' => $mostFrequentCategory->category ? $mostFrequentCategory->category->name : 'Uncategorized',
                    'count' => $mostFrequentCategory->count
                ] : null,
            ]
        ]);
    }
}
