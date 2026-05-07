<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GeminiService;

$service = new GeminiService();

echo "Testing Gemini Chart Generation...\n";

// Simulate a user asking for a comparison which should trigger a chart
$response = $service->chat("Compare my food and transport spending. Food: 5000, Transport: 3000.", [
    'balance' => 50000,
    'monthlyIncome' => 30000,
    'monthlyExpenses' => 20000
]);

echo "\n--- RAW RESPONSE ---\n";
echo $response;
echo "\n--------------------\n";

if (strpos($response, '```json') !== false || strpos($response, '"type":') !== false) {
    echo "\n✅ SUCCESS: Response contains JSON or chart data structure.\n";
} else {
    echo "\n❌ FAILURE: Response does NOT contain JSON chart data.\n";
}
