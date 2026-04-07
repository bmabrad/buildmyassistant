<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LaunchpadController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\Impersonating;
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

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'message' => 'required|string|max:5000',
    ]);

    \Illuminate\Support\Facades\Mail::raw(
        "Name: {$request->name}\nEmail: {$request->email}\n\n{$request->message}",
        function ($mail) use ($request) {
            $mail->to('hello@buildmyassistant.co')
                ->replyTo($request->email, $request->name)
                ->subject("Contact form: {$request->name}");
        }
    );

    return back()->with('success', 'Thanks for your message. We will get back to you soon.');
})->name('contact.store');

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

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/magic', [AuthController::class, 'sendMagicLink'])->name('login.magic');
    Route::get('/auth/magic/{token}', [AuthController::class, 'verifyMagicLink'])->name('auth.magic');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Dashboard routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/password', [DashboardController::class, 'updatePassword'])->name('dashboard.password');
    Route::post('/dashboard/billing', [DashboardController::class, 'billing'])->name('dashboard.billing');
    Route::get('/dashboard/new-build', [DashboardController::class, 'newBuild'])->name('dashboard.new-build');
    Route::post('/dashboard/new-build', [DashboardController::class, 'chargeNewBuild']);
});

// Admin impersonation
Route::middleware('auth')->group(function () {
    Route::post('/admin/impersonate/{user}', [ImpersonationController::class, 'start'])->name('admin.impersonate');
    Route::post('/admin/stop-impersonating', [ImpersonationController::class, 'stop'])->name('admin.stop-impersonating');
});

// Stripe webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');
