<?php

/**
 * Simple script to test Gemini API connectivity
 * Run this from command line: php test_gemini.php
 */

// Test with cURL directly
function testWithCurl($apiKey) {
    echo "Testing with cURL...\n";
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
    
    $data = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Hello, are you working?']
                ]
            ]
        ]
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "cURL Error: $error\n";
    }
    
    if ($response) {
        echo "Response: " . substr($response, 0, 500) . "\n";
    }
    
    echo "\nVerbose Log:\n$verboseLog\n";
    
    return $httpCode == 200;
}

// Test with file_get_contents
function testWithFileGetContents($apiKey) {
    echo "\n\nTesting with file_get_contents...\n";
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
    
    $data = json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Hello, are you working?']
                ]
            ]
        ]
    ]);
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $data,
            'timeout' => 60,
        ]
    ];
    
    $context = stream_context_create($options);
    
    try {
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
            return false;
        }
        
        echo "Response: " . substr($response, 0, 500) . "\n";
        return true;
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
echo "=== Gemini API Connection Test ===\n\n";

// Use the default API key from your service
$apiKey = 'AIzaSyAAjM6pFbn9brrW1J_Wt6BPlp_BO9EGEU8';

echo "Testing connectivity to Gemini API...\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Check if we can resolve the domain
echo "Checking DNS resolution...\n";
$ip = gethostbyname('generativelanguage.googleapis.com');
echo "Resolved IP: $ip\n\n";

// Test with cURL
$curlSuccess = testWithCurl($apiKey);

// Test with file_get_contents
$fgcSuccess = testWithFileGetContents($apiKey);

echo "\n=== Test Summary ===\n";
echo "cURL Test: " . ($curlSuccess ? "SUCCESS" : "FAILED") . "\n";
echo "file_get_contents Test: " . ($fgcSuccess ? "SUCCESS" : "FAILED") . "\n";

if (!$curlSuccess && !$fgcSuccess) {
    echo "\nPossible issues:\n";
    echo "1. Network/firewall blocking the connection\n";
    echo "2. Invalid API key\n";
    echo "3. Proxy configuration needed\n";
    echo "4. SSL certificate issues\n";
}
