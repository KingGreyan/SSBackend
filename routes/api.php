<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth.token', 'check.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // User Profile
    Route::put('/profile', [App\Http\Controllers\UserController::class, 'updateProfile']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/analysis', [\App\Http\Controllers\AnalysisController::class, 'index']);
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
    
    // Budget management routes
    Route::get('/budgets', [\App\Http\Controllers\CategoryController::class, 'getBudgetStatus']);
    Route::post('/budgets', [\App\Http\Controllers\CategoryController::class, 'setBudget']);
    Route::delete('/budgets/{id}', [\App\Http\Controllers\CategoryController::class, 'deleteBudget']);
    
    // AI routes
    Route::post('/ai/api-key', [\App\Http\Controllers\AIController::class, 'saveApiKey']);
    Route::get('/ai/check-key', [\App\Http\Controllers\AIController::class, 'checkApiKey']);
    Route::get('/ai/test-connection', [\App\Http\Controllers\AIController::class, 'testApiConnection']);
    Route::get('/ai/insights', [\App\Http\Controllers\AIController::class, 'getInsights']);
    Route::post('/ai/analyze', [\App\Http\Controllers\AIController::class, 'analyzeSpending']);
    Route::get('/ai/budget-recommendations', [\App\Http\Controllers\AIController::class, 'getBudgetRecommendations']);
    Route::post('/ai/chat', [\App\Http\Controllers\AIController::class, 'chat']);
    
    // Todo/Schedule routes
    Route::get('/todos', [\App\Http\Controllers\TodoController::class, 'index']);
    Route::get('/todos/{year}/{month}', [\App\Http\Controllers\TodoController::class, 'getByMonth']);
    Route::post('/todos', [\App\Http\Controllers\TodoController::class, 'store']);
    Route::put('/todos/{id}', [\App\Http\Controllers\TodoController::class, 'update']);
    Route::post('/todos/{id}/toggle', [\App\Http\Controllers\TodoController::class, 'toggleComplete']);
    Route::delete('/todos/{id}', [\App\Http\Controllers\TodoController::class, 'destroy']);
    
    // User management routes (admin only)
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::put('/users/{id}', [\App\Http\Controllers\UserController::class, 'update']);
    Route::post('/users/{id}/toggle-status', [\App\Http\Controllers\UserController::class, 'toggleStatus']);
});
