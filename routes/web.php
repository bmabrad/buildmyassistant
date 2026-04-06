<?php

use App\Http\Controllers\LaunchpadController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\ValidateLaunchpadToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Launchpad routes
Route::get('/launchpad', [LaunchpadController::class, 'show'])->name('launchpad');
Route::post('/launchpad/checkout', [LaunchpadController::class, 'checkout'])->name('launchpad.checkout');
Route::get('/launchpad/success', [LaunchpadController::class, 'success'])->name('launchpad.success');

Route::middleware(ValidateLaunchpadToken::class)->group(function () {
    Route::get('/launchpad/{token}', [LaunchpadController::class, 'chat'])->name('launchpad.chat');
});

// Stripe webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');
