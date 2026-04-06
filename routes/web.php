<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\LaunchpadController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\ValidateLaunchpadToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('terms');
})->name('terms');

// Blog routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/feed', [BlogController::class, 'feed'])->name('blog.feed');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Launchpad routes
Route::get('/launchpad', [LaunchpadController::class, 'show'])->name('launchpad');
Route::post('/launchpad/checkout', [LaunchpadController::class, 'checkout'])->name('launchpad.checkout');
Route::get('/launchpad/success', [LaunchpadController::class, 'success'])->name('launchpad.success');

Route::middleware(ValidateLaunchpadToken::class)->group(function () {
    Route::get('/launchpad/{token}', [LaunchpadController::class, 'chat'])->name('launchpad.chat');
    Route::get('/launchpad/{token}/instructions.txt', [LaunchpadController::class, 'downloadInstructions'])->name('launchpad.instructions');
    Route::get('/launchpad/{token}/chat.txt', [LaunchpadController::class, 'downloadChat'])->name('launchpad.chat.download');
});

// Stripe webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');
