<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqService
{
    private $apiKey;
    private $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct($apiKey = null)
    {
        // Use provided key or environment variable
        $this->apiKey = $apiKey ?: env('GROQ_API_KEY');
    }

    /**
     * Test connection to Groq API
     */
    public function testConnection()
    {
        try {
            $response = Http::timeout(15)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => 'mixtral-8x7b-32768',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Hello'
                        ]
                    ],
                    'max_tokens' => 100,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful'
                ];
            }

            return [
                'success' => false,
                'message' => 'API returned error: ' . $response->body(),
                'status' => $response->status()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate content using Groq AI with retry logic
     */
    public function generateContent($prompt, $retries = 2)
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt <= $retries) {
            try {
                $response = Http::timeout(60)
                    ->connectTimeout(30)
                    ->retry(2, 1000)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->baseUrl, [
                        'model' => 'mixtral-8x7b-32768',
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 8192,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Validate response structure
                    if (isset($data['choices'][0]['message']['content'])) {
                        return $data['choices'][0]['message']['content'];
                    }
                    
                    // Check for error in response
                    if (isset($data['error'])) {
                        $errorMsg = $data['error']['message'] ?? 'Unknown API error';
                        return "API Error: {$errorMsg}";
                    }
                    
                    return 'No response generated from AI';
                }

                // Handle HTTP errors
                $statusCode = $response->status();
                $errorBody = $response->body();
                
                if ($statusCode === 429) {
                    return "The AI service is currently rate limited. Please wait a moment and try again.\n\n_Tip: This usually resolves quickly._";
                }
                
                return "HTTP Error {$statusCode}: {$errorBody}";
                
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $lastError = $e;
                $attempt++;
                
                if ($attempt <= $retries) {
                    sleep(pow(2, $attempt));
                    continue;
                }
                
                return "Connection Error: Unable to reach Groq API after {$retries} retries. Please check your internet connection or try again later. Details: " . $e->getMessage();
                
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;
                
                if ($e->getCode() === 429 || strpos($e->getMessage(), '429') !== false) {
                    return "The AI service is currently rate limited. Please wait a moment and try again.\n\n_Tip: This usually resolves quickly._";
                }

                if ($attempt <= $retries) {
                    sleep(1);
                    continue;
                }
                
                return 'Error: ' . $e->getMessage();
            }
        }

        return 'Error: Maximum retries exceeded. ' . ($lastError ? $lastError->getMessage() : 'Unknown error');
    }

    /**
     * Get financial insights based on user data
     */
    public function getFinancialInsights($userData)
    {
        $prompt = "You are an expert financial advisor. Analyze this user's financial data and provide comprehensive insights:\n\n";
        $prompt .= "📊 FINANCIAL OVERVIEW:\n";
        $prompt .= "Total Income: ₱" . number_format($userData['totalIncome'], 2) . "\n";
        $prompt .= "Total Expenses: ₱" . number_format($userData['totalExpenses'], 2) . "\n";
        $prompt .= "Current Balance: ₱" . number_format($userData['balance'], 2) . "\n";
        $prompt .= "Current Month Income: ₱" . number_format($userData['currentMonthIncome'], 2) . "\n";
        $prompt .= "Current Month Expenses: ₱" . number_format($userData['currentMonthExpenses'], 2) . "\n\n";
        
        if (!empty($userData['categoryStats'])) {
            $prompt .= "💳 TOP SPENDING CATEGORIES:\n";
            foreach ($userData['categoryStats'] as $cat) {
                $prompt .= "- {$cat['name']}: ₱" . number_format($cat['expense'], 2) . "\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Please provide a detailed analysis with:\n\n";
        $prompt .= "## 🎯 Key Financial Insights\n";
        $prompt .= "Provide 4-5 specific, actionable insights about their financial situation.\n\n";
        
        $prompt .= "## ⚠️ Areas of Concern\n";
        $prompt .= "Identify any red flags or concerning patterns.\n\n";
        
        $prompt .= "## ✅ Strengths\n";
        $prompt .= "Highlight what they're doing well.\n\n";
        
        $prompt .= "## 💡 Actionable Recommendations\n";
        $prompt .= "Provide 3-5 specific, practical steps they can take immediately.\n\n";
        
        $prompt .= "## 📈 Financial Health Score\n";
        $prompt .= "Rate their overall financial health on a scale of 1-10 and explain why.\n\n";
        
        $prompt .= "Format your response in clear markdown with emojis for better readability. Be specific with numbers and percentages.";

        return $this->generateContent($prompt);
    }

    /**
     * Get budget recommendations
     */
    public function getBudgetRecommendations($spendingData)
    {
        $prompt = "You are a financial planning expert. Based on this spending pattern, create detailed monthly budget recommendations:\n\n";
        
        $prompt .= "💰 CURRENT SPENDING BREAKDOWN:\n";
        $totalSpent = 0;
        foreach ($spendingData as $category) {
            $prompt .= "- {$category['name']}: ₱" . number_format($category['spent'], 2) . "\n";
            $totalSpent += $category['spent'];
        }
        $prompt .= "\nTotal Monthly Spending: ₱" . number_format($totalSpent, 2) . "\n\n";

        $prompt .= "Please provide:\n\n";
        $prompt .= "## 📊 Recommended Budget Allocation\n";
        $prompt .= "For each category, provide:\n";
        $prompt .= "- Recommended monthly budget amount\n";
        $prompt .= "- Percentage of total income (if overspending, suggest reduction)\n";
        $prompt .= "- Brief justification\n\n";
        
        $prompt .= "## 🎯 Budget Strategy\n";
        $prompt .= "Apply the 50/30/20 rule:\n";
        $prompt .= "- 50% for Needs (essentials)\n";
        $prompt .= "- 30% for Wants (lifestyle)\n";
        $prompt .= "- 20% for Savings & Debt\n\n";
        
        $prompt .= "## 💡 Quick Wins\n";
        $prompt .= "Identify 2-3 categories where they can easily cut costs.\n\n";
        
        $prompt .= "## 📈 Expected Impact\n";
        $prompt .= "Calculate potential monthly savings if recommendations are followed.\n\n";
        
        $prompt .= "Format as clear markdown with specific peso amounts. Be realistic and practical.";

        return $this->generateContent($prompt);
    }

    /**
     * Analyze spending patterns
     */
    public function analyzeSpending($transactions)
    {
        $prompt = "You are a data analyst specializing in personal finance. Analyze these recent transactions and provide deep insights:\n\n";
        
        $prompt .= "📝 RECENT TRANSACTIONS:\n";
        foreach ($transactions as $tx) {
            $prompt .= "- {$tx['category']}: ₱" . number_format($tx['amount'], 2) . " ({$tx['type']}) on {$tx['date']}\n";
        }

        $prompt .= "\n\nProvide a comprehensive analysis:\n\n";
        
        $prompt .= "## 📊 Spending Patterns\n";
        $prompt .= "Identify recurring patterns, habits, and trends in the data.\n\n";
        
        $prompt .= "## 🔍 Key Observations\n";
        $prompt .= "Highlight the most important findings from the transaction history.\n\n";
        
        $prompt .= "## ⚠️ Anomalies & Red Flags\n";
        $prompt .= "Identify any unusual, irregular, or concerning expenses.\n\n";
        
        $prompt .= "## 💡 Optimization Opportunities\n";
        $prompt .= "Suggest specific ways to save money based on the spending patterns.\n\n";
        
        $prompt .= "## 📈 Trend Analysis\n";
        $prompt .= "Describe if spending is increasing, decreasing, or stable. Predict future trends.\n\n";
        
        $prompt .= "## 🎯 Action Items\n";
        $prompt .= "Provide 3-5 specific, actionable steps to improve spending habits.\n\n";
        
        $prompt .= "Be specific with numbers, percentages, and concrete examples. Format in clear markdown.";

        return $this->generateContent($prompt);
    }

    /**
     * Chat with AI financial advisor
     */
    public function chat($message, $context = [])
    {
        $prompt = "You are an expert financial advisor and data analyst. You help users understand their finances through clear, actionable advice.\n\n";
        
        $prompt .= "PERSONA: PROFESSIONAL & FRIENDLY\n";
        $prompt .= "- Tone: Professional, knowledgeable, yet warm and encouraging.\n";
        $prompt .= "- Style: Use clear, concise language. Avoid jargon where possible. Use emojis to make the conversation engaging but not childish.\n";
        $prompt .= "- Accuracy: When analyzing data, be PRECISE. Use the exact numbers provided. Do not hallucinate data.\n\n";

        // --- TEMPORAL CONTEXT ---
        $prompt .= "📅 CURRENT DATE: " . now()->toDateString() . " (" . now()->format('l') . ")\n";
        $prompt .= "All relative dates (yesterday, last week, last month) must be calculated based on this date.\n\n";

        // --- TOOL DEFINITIONS (Only if NO tool result yet) ---
        if (empty($context['tool_result'])) {
            $prompt .= "🛠️ AVAILABLE TOOLS:\n";
            $prompt .= "You have access to the following tools to query the user's database. USE THEM whenever the user asks for specific data you don't have.\n";
            $prompt .= "1. `get_transactions(start_date, end_date, type, category, limit)`: Fetch recent transactions. Dates in YYYY-MM-DD. Type: 'income'/'expense'. Category: String.\n";
            $prompt .= "2. `get_daily_trends(start_date, end_date)`: Get daily income/expense totals for a date range.\n";
            $prompt .= "3. `get_category_summary(month, year)`: Get spending by category for a specific month (1-12) and year.\n\n";

            $prompt .= "PROTOCOL: TOOL USE & CLARIFICATION\n";
            $prompt .= "1. If the user's request is VAGUE (e.g., 'How much did I spend?'), ASK CLARIFYING QUESTIONS first. Do not guess.\n";
            $prompt .= "2. If you need data, return ONLY a Valid JSON object (no markdown formatting needed) with this structure:\n";
            $prompt .= "{\n";
            $prompt .= "  \"tool\": \"get_transactions\",\n";
            $prompt .= "  \"params\": {\n";
            $prompt .= "    \"limit\": 5,\n";
            $prompt .= "    \"type\": \"expense\"\n";
            $prompt .= "  }\n";
            $prompt .= "}\n";
            $prompt .= "Do NOT include any other text if you are calling a tool.\n\n";
        }

        // --- CONTEXT ---
        if (!empty($context['tool_result'])) {
            $prompt .= "✅ TOOL RESULT - DATA RECEIVED:\n";
            $prompt .= json_encode($context['tool_result']) . "\n\n";
            $prompt .= "INSTRUCTIONS:\n";
            $prompt .= "- Answer the user's question using this data.\n";
            $prompt .= "- Be specific with the amounts and dates.\n";
            $prompt .= "- DO NOT call another tool.\n";
        } elseif (!empty($context)) {
            $prompt .= "📊 GENERAL CONTEXT:\n";
            $prompt .= "- Current Balance: ₱" . number_format($context['balance'] ?? 0, 2) . "\n";
            $prompt .= "- Monthly Income: ₱" . number_format($context['monthlyIncome'] ?? 0, 2) . "\n";
            $prompt .= "- Monthly Expenses: ₱" . number_format($context['monthlyExpenses'] ?? 0, 2) . "\n\n";
        }

        $prompt .= "USER QUESTION: " . $message . "\n\n";

        if (empty($context['tool_result'])) {
            $prompt .= "INSTRUCTIONS (INITIAL PASS):\n";
            $prompt .= "- IF the question is ambiguous, ASK for clarification.\n";
            $prompt .= "- IF you need data, call a tool (return JSON only).\n";
            $prompt .= "- OTHERWISE, answer based on general context.\n";
        } else {
            $prompt .= "INSTRUCTIONS (FINAL ANSWER):\n";
            $prompt .= "- Analyze the provided TOOL RESULT data.\n";
            $prompt .= "- Synthesize a friendly, professional response.\n";
            $prompt .= "- START your response with a friendly opening.\n";
            $prompt .= "- DO NOT say 'Based on the data', 'According to the database', or 'The records show'. Just say 'You spent...' or 'Your total is...'.\n";
            $prompt .= "- Be natural and conversational.\n";
            $prompt .= "- Use markdown for lists and bolding key numbers.\n";
        }
        
        $prompt .= "IMPORTANT - SUGGESTIONS (REQUIRED for final answers):\n";
        $prompt .= "If you are giving a final answer (NOT calling a tool), you MUST end with a JSON object for suggestions.\n";
        $prompt .= "Ensure the JSON is strictly correctly formatted and separated from the text.\n";
        $prompt .= "```json\n{ \"suggestions\": [\"Follow-up Q1\", \"Follow-up Q2\"] }\n```\n";

        return $this->generateContent($prompt);
    }
}
