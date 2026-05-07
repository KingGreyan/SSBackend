<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $range = $request->get('range', 'weekly'); // weekly, monthly, yearly
        $refDate = $request->has('date') ? Carbon::parse($request->get('date')) : Carbon::now();
        
        // Base query
        $query = $user->transactions();

        // Calculate Time Data
        $chartData = [];
        $income = 0;
        $expenses = 0;
        
        if ($range === 'weekly') {
            $start = $refDate->copy()->startOfWeek();
            $end = $refDate->copy()->endOfWeek();
            
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dayIncome = $user->transactions()
                    ->where('type', 'income')
                    ->whereDate('date', $date->toDateString())
                    ->sum('amount');
                $dayExpenses = $user->transactions()
                    ->where('type', 'expense')
                    ->whereDate('date', $date->toDateString())
                    ->sum('amount');
                    
                $chartData[] = [
                    'date' => $date->format('D'),
                    'fullDate' => $date->toDateString(),
                    'income' => (float) $dayIncome,
                    'expenses' => (float) $dayExpenses,
                ];
                $income += $dayIncome;
                $expenses += $dayExpenses;
            }
        } elseif ($range === 'monthly') {
            $start = $refDate->copy()->startOfMonth();
            $end = $refDate->copy()->endOfMonth();
            
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dayIncome = $user->transactions()
                    ->where('type', 'income')
                    ->whereDate('date', $date->toDateString())
                    ->sum('amount');
                $dayExpenses = $user->transactions()
                    ->where('type', 'expense')
                    ->whereDate('date', $date->toDateString())
                    ->sum('amount');
                
                // Group by week or just standard days? Monthly usually shows days.
                $chartData[] = [
                    'date' => $date->format('j'), // Day of month
                    'fullDate' => $date->toDateString(),
                    'income' => (float) $dayIncome,
                    'expenses' => (float) $dayExpenses,
                ];
                $income += $dayIncome;
                $expenses += $dayExpenses;
            }
        } elseif ($range === 'yearly') {
            $start = $refDate->copy()->startOfYear();
            $end = $refDate->copy()->endOfYear();

            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $monthIncome = $user->transactions()
                    ->where('type', 'income')
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->sum('amount');
                $monthExpenses = $user->transactions()
                    ->where('type', 'expense')
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->sum('amount');

                $chartData[] = [
                    'date' => $date->format('M'),
                    'fullDate' => $date->format('Y-m'),
                    'income' => (float) $monthIncome,
                    'expenses' => (float) $monthExpenses,
                ];
                $income += $monthIncome;
                $expenses += $monthExpenses;
            }
        } else {
             // Fallback to last 7 days logic if needed, or error. defaulting to weekly above.
        }

        // Stats (Overall Balance)
        $totalIncome = $user->transactions()->where('type', 'income')->sum('amount');
        $totalExpenses = $user->transactions()->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpenses;
        
        // Category Breakdown (using the selected range to be relevant? Or overall? User asked for Cash Flow view. Usually breakdown matches view, but Dashboard top cards are usually "This Month". Let's keep cards as "Current Month" vs "Last Month" for consistency, but update chartData separately.)
        
        // Actually, let's keep the Top Cards fixed to "Current Month" for stability, 
        // OR should they reflect the navigation? "Weekly" view -> "This Week's Income"?
        // The user request was "in dashboard cash flow thier weekly montly and yearly".
        // This implies the CHART is the main target.
        // I will keep the Summary Cards as "Current Month" for now to avoid confusion, 
        // OR I can make them dynamic based on $range. 
        // Making them dynamic is better UX.
        
        // Re-calculating $currentMonth... stats based on $range?
        // Let's stick to "Current Month" for the cards to keep it simple and standard, 
        // but the Chart will be dynamic.
        
        // ... (Existing Card Logic reuse)
        
         // Get current month stats (Fixed to actual current month for the cards)
        $currentMonthIncome = $user->transactions()
            ->where('type', 'income')
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');
            
        $currentMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');
        
        // Get last month stats for comparison
        $lastMonthIncome = $user->transactions()
            ->where('type', 'income')
            ->whereMonth('date', Carbon::now()->subMonth()->month)
            ->whereYear('date', Carbon::now()->subMonth()->year)
            ->sum('amount');
            
        $lastMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', Carbon::now()->subMonth()->month)
            ->whereYear('date', Carbon::now()->subMonth()->year)
            ->sum('amount');
        
        // Calculate trends
        $incomeTrend = $lastMonthIncome > 0 
            ? (($currentMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100 
            : 0;
            
        $expenseTrend = $lastMonthExpenses > 0 
            ? (($currentMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100 
            : 0;

        // Category Breakdown - Overall? Or top 5? Keeping overall top 5.
        $categoryStats = $user->transactions()
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($items) {
                $category = $items->first()->category;
                $income = $items->where('type', 'income')->sum('amount');
                $expense = $items->where('type', 'expense')->sum('amount');
                return [
                    'name' => $category ? $category->name : 'Unknown',
                    'income' => (float) $income,
                    'expense' => (float) $expense,
                    'total' => (float) ($income + $expense),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(5);

        // Recent transactions
        $recentTransactions = $user->transactions()
            ->with('category')
            ->orderBy('date', 'desc')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'category' => $transaction->category ? $transaction->category->name : 'Unknown',
                    'note' => $transaction->note,
                    'date' => $transaction->date ? Carbon::parse($transaction->date)->toDateString() : $transaction->created_at->toDateString(),
                    'timestamp' => $transaction->created_at->toISOString(),
                ];
            });

        return response()->json([
            'summary' => [
                'balance' => (float) $balance,
                'totalIncome' => (float) $totalIncome,
                'totalExpenses' => (float) $totalExpenses,
                'currentMonthIncome' => (float) $currentMonthIncome,
                'currentMonthExpenses' => (float) $currentMonthExpenses,
                'incomeTrend' => round($incomeTrend, 2),
                'expenseTrend' => round($expenseTrend, 2),
                'transactionCount' => 0, // Unused
                'incomeCount' => 0,
                'expenseCount' => 0,
            ],
            'chartData' => [
                'chart' => $chartData, // Renamed from last7Days
                'categoryStats' => $categoryStats,
            ],
            'recentTransactions' => $recentTransactions,
            'rangeInfo' => [
                'range' => $range,
                'totalIncome' => $income,
                'totalExpenses' => $expenses,
            ]
        ]);
    }
}
