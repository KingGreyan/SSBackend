<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiService;
use App\Models\CategoryBudget;
use Illuminate\Support\Facades\Crypt;

class AIController extends Controller
{
    /**
     * Save or update user's Gemini API key
     */
    public function saveApiKey(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
        ]);

        $user = auth()->user();
        $user->gemini_api_key = Crypt::encryptString($request->api_key);
        $user->save();

        return response()->json([
            'message' => 'API key saved successfully',
            'has_api_key' => true,
        ]);
    }

    /**
     * Check if user has API key
     */
    public function checkApiKey()
    {
        $user = auth()->user();
        return response()->json([
            'has_api_key' => !empty($user->gemini_api_key),
        ]);
    }

    /**
     * Test API connection
     */
    public function testApiConnection()
    {
        $user = auth()->user();
        $apiKey = !empty($user->gemini_api_key) ? Crypt::decryptString($user->gemini_api_key) : null;
        
        $gemini = new GeminiService($apiKey);
        $result = $gemini->testConnection();
        
        return response()->json($result);
    }

    /**
     * Get AI financial insights
     */
    public function getInsights(Request $request)
    {
        $user = auth()->user();

        // Gather user financial data
        $totalIncome = $user->transactions()->where('type', 'income')->sum('amount');
        $totalExpenses = $user->transactions()->where('type', 'expense')->sum('amount');
        $balance = $totalIncome - $totalExpenses;

        $currentMonthIncome = $user->transactions()
            ->where('type', 'income')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        $currentMonthExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        // Get category stats
        $categoryStats = $user->transactions()
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($items) {
                $category = $items->first()->category;
                return [
                    'name' => $category ? $category->name : 'Unknown',
                    'expense' => $items->where('type', 'expense')->sum('amount'),
                ];
            })
            ->sortByDesc('expense')
            ->take(5)
            ->values();

        $userData = [
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'balance' => $balance,
            'currentMonthIncome' => $currentMonthIncome,
            'currentMonthExpenses' => $currentMonthExpenses,
            'categoryStats' => $categoryStats,
        ];

        $apiKey = !empty($user->gemini_api_key) ? Crypt::decryptString($user->gemini_api_key) : null;
        $gemini = new GeminiService($apiKey);
        $insights = $gemini->getFinancialInsights($userData);

        return response()->json([
            'insights' => $insights,
            'data' => $userData,
        ]);
    }

    /**
     * Analyze spending patterns
     */
    public function analyzeSpending(Request $request)
    {
        $user = auth()->user();

        $transactions = $user->transactions()
            ->with('category')
            ->orderBy('date', 'desc')
            ->take(20)
            ->get()
            ->map(function ($tx) {
                return [
                    'category' => $tx->category ? $tx->category->name : 'Unknown',
                    'amount' => $tx->amount,
                    'type' => $tx->type,
                    'date' => $tx->date,
                ];
            });

        $apiKey = !empty($user->gemini_api_key) ? Crypt::decryptString($user->gemini_api_key) : null;
        $gemini = new GeminiService($apiKey);
        $analysis = $gemini->analyzeSpending($transactions);

        return response()->json([
            'analysis' => $analysis,
        ]);
    }

    /**
     * Get budget recommendations
     */
    public function getBudgetRecommendations(Request $request)
    {
        $user = auth()->user();

        // Get spending by category for current month
        $spendingData = $user->transactions()
            ->with('category')
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->get()
            ->groupBy('category_id')
            ->map(function ($items) {
                $category = $items->first()->category;
                return [
                    'name' => $category ? $category->name : 'Unknown',
                    'spent' => $items->sum('amount'),
                ];
            })
            ->values();

        $apiKey = !empty($user->gemini_api_key) ? Crypt::decryptString($user->gemini_api_key) : null;
        $gemini = new GeminiService($apiKey);
        $recommendations = $gemini->getBudgetRecommendations($spendingData);

        return response()->json([
            'recommendations' => $recommendations,
            'currentSpending' => $spendingData,
        ]);
    }

    /**
     * Chat with AI financial advisor
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = auth()->user();

        // 1. Initial Context
        $totalIncome = $user->transactions()->where('type', 'income')->sum('amount');
        $totalExpenses = $user->transactions()->where('type', 'expense')->sum('amount');
        
        $context = [
            'balance' => $totalIncome - $totalExpenses,
            'monthlyIncome' => $user->transactions()->where('type', 'income')->whereMonth('date', now()->month)->sum('amount'),
            'monthlyExpenses' => $user->transactions()->where('type', 'expense')->whereMonth('date', now()->month)->sum('amount'),
        ];

        $apiKey = !empty($user->gemini_api_key) ? Crypt::decryptString($user->gemini_api_key) : null;
        $gemini = new GeminiService($apiKey);
        
        // 2. First Pass: Ask AI
        $response = $gemini->chat($request->message, $context);

        // 3. Check for Tool Use (JSON)
        $toolData = $this->parseToolCall($response);
        
        if ($toolData) {
            // Execute Tool
            $toolResult = $this->executeTool($toolData['tool'], $toolData['params'] ?? []);
            
            // 4. Second Pass: Re-prompt with Data
            // We pass 'tool_result' which triggers the "Final Answer" logic in GeminiService
            $finalResponse = $gemini->chat($request->message, ['tool_result' => $toolResult]);
            
            // SAFETY CHECK: If final response still looks like a tool call, force a text response
            $secondTool = $this->parseToolCall($finalResponse);
            if ($secondTool) {
                return response()->json([
                    'response' => "I found the data but I'm having trouble summarizing it. Here is the raw result: " . json_encode($toolResult),
                    'message' => $request->message,
                ]);
            }

            return response()->json([
                'response' => $finalResponse,
                'message' => $request->message,
                'tool_used' => $toolData['tool']
            ]);
        }

        return response()->json([
            'response' => $response,
            'message' => $request->message,
        ]);
    }

    // --- TOOL EXECUTION LOGIC ---

    private function parseToolCall($text)
    {
        // 1. Try Markdown Code Block
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $json = json_decode($matches[1], true);
            if (isset($json['tool'])) return $json;
        }
        
        // 2. Try Raw JSON (if text starts/ends with braces)
        $json = json_decode($text, true);
        if (isset($json['tool'])) return $json;

        // 3. Try to find { "tool": ... } pattern anywhere in text
        if (preg_match('/(\{[\s\S]*?"tool"\s*:\s*"[^"]+"[\s\S]*?\})/', $text, $matches)) {
            $json = json_decode($matches[1], true);
            if (isset($json['tool'])) return $json;
        }
        
        return null;
    }

    private function executeTool($toolName, $params)
    {
        switch ($toolName) {
            case 'get_transactions':
                return $this->fetchTransactions($params);
            case 'get_daily_trends':
                return $this->fetchDailyTrends($params);
            case 'get_category_summary':
                return $this->fetchCategorySummary($params);
            default:
                return ["error" => "Unknown tool: $toolName"];
        }
    }

    private function fetchTransactions($params)
    {
        $query = auth()->user()->transactions()->with('category')->orderBy('date', 'desc');

        if (!empty($params['start_date'])) $query->whereDate('date', '>=', $params['start_date']);
        if (!empty($params['end_date'])) $query->whereDate('date', '<=', $params['end_date']);
        if (!empty($params['type'])) $query->where('type', $params['type']);
        if (!empty($params['category'])) {
            $query->whereHas('category', function($q) use ($params) {
                $q->where('name', 'like', '%' . $params['category'] . '%');
            });
        }

        $limit = $params['limit'] ?? 10;
        
        return $query->take($limit)->get()->map(function($t) {
            return sprintf(
                "%s | %s | %s | ₱%s | %s",
                $t->date,
                $t->type,
                $t->category?->name ?? 'Uncategorized',
                number_format($t->amount, 2),
                $t->description
            );
        });
    }

    private function fetchDailyTrends($params)
    {
        $startDate = $params['start_date'] ?? now()->subDays(7)->toDateString();
        $endDate = $params['end_date'] ?? now()->toDateString();
        
        return auth()->user()->transactions()
            ->selectRaw('DATE(date) as day, type, SUM(amount) as total')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('day', 'type')
            ->orderBy('day')
            ->get()
            ->groupBy('day')
            ->map(function($group) {
                return [
                    'income' => $group->where('type', 'income')->sum('total'),
                    'expense' => $group->where('type', 'expense')->sum('total')
                ];
            });
    }

    private function fetchCategorySummary($params)
    {
        $month = $params['month'] ?? now()->month;
        $year = $params['year'] ?? now()->year;
        
        return auth()->user()->transactions()
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, SUM(transactions.amount) as total, COUNT(*) as count')
            ->where('transactions.type', 'expense') // Usually summary is for expenses
            ->whereMonth('transactions.date', $month)
            ->whereYear('transactions.date', $year)
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
    }
}
