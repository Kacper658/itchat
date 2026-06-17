<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publiczne (gość / niezalogowany)
|--------------------------------------------------------------------------
*/
Route::get('/geo/detect', [GeoController::class, 'detect']);
Route::post('/chat/guest', [ChatController::class, 'guestMessage']);
Route::get('/billing/plans', [BillingController::class, 'plans']);

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Webhooki operatorów płatności (weryfikowane podpisem, nie auth)
Route::prefix('webhooks')->group(function () {
    Route::post('przelewy24', [WebhookController::class, 'przelewy24']);
});

/*
|--------------------------------------------------------------------------
| Wymagające konta (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::get('me',       [AuthController::class, 'me']);
    });

    // Chat
    Route::prefix('chat')->group(function () {
        Route::get('conversations',                     [ChatController::class, 'conversations']);
        Route::post('conversations',                    [ChatController::class, 'storeConversation']);
        Route::get('conversations/{conversation}/messages', [ChatController::class, 'messages']);
        Route::delete('conversations/{conversation}',   [ChatController::class, 'destroyConversation']);
        Route::post('messages',                         [ChatController::class, 'sendMessage']);
    });

    // Billing
    Route::prefix('billing')->group(function () {
        Route::get('wallet',        [BillingController::class, 'wallet']);
        Route::get('transactions',  [BillingController::class, 'transactions']);
        Route::post('deposit',      [BillingController::class, 'deposit']);
        Route::post('subscribe',    [BillingController::class, 'subscribe']);
        Route::get('subscription',  [BillingController::class, 'subscription']);
    });

    // Użytkownik
    Route::prefix('user')->group(function () {
        Route::get('settings',          [UserController::class, 'settings']);
        Route::put('settings',          [UserController::class, 'updateSettings']);
        Route::put('password',          [UserController::class, 'updatePassword']);
        Route::delete('/',              [UserController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Panel administratora
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('stats',                       [AdminController::class, 'stats']);
    Route::get('users',                       [AdminController::class, 'users']);
    Route::patch('users/{user}',              [AdminController::class, 'updateUser']);
    Route::post('users/{user}/bonus',         [AdminController::class, 'bonus']);
    Route::get('transactions',                [AdminController::class, 'transactions']);
    Route::get('plans',                       [AdminController::class, 'plans']);
    Route::put('plans/{plan}',                [AdminController::class, 'updatePlan']);
    Route::get('messages/export',             [AdminController::class, 'exportMessages']);
});
