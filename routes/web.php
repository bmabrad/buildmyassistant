<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LaunchpadController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\Impersonating;
use App\Http\Middleware\ValidateLaunchpadToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $articles = \App\Models\Article::published()
        ->orderByDesc('published_at')
        ->take(3)
        ->get();

    return view('home', ['articles' => $articles]);
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

// Article routes
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/feed', [ArticleController::class, 'feed'])->name('articles.feed');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');

// Launchpad routes
Route::get('/launchpad', [LaunchpadController::class, 'show'])->name('launchpad');
Route::post('/launchpad/checkout', [LaunchpadController::class, 'checkout'])->name('launchpad.checkout');
Route::get('/launchpad/success', [LaunchpadController::class, 'success'])->name('launchpad.success');

Route::middleware(ValidateLaunchpadToken::class)->group(function () {
    Route::get('/launchpad/{token}', [LaunchpadController::class, 'chat'])->name('launchpad.chat');
    Route::get('/launchpad/{token}/playbook.pdf', [LaunchpadController::class, 'downloadPlaybookPdf'])->name('launchpad.playbook.pdf');
    Route::get('/launchpad/{token}/instructions.md', [LaunchpadController::class, 'downloadInstructionsMd'])->name('launchpad.instructions.md');
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
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/verify-email/{user}', [SettingsController::class, 'verifyEmail'])->name('settings.verify-email');
    Route::post('/settings/cancel-email', [SettingsController::class, 'cancelPendingEmail'])->name('settings.cancel-email');
});

// Admin impersonation
Route::middleware('auth')->group(function () {
    Route::post('/admin/impersonate/{user}', [ImpersonationController::class, 'start'])->name('admin.impersonate');
    Route::post('/admin/stop-impersonating', [ImpersonationController::class, 'stop'])->name('admin.stop-impersonating');
});

// Stripe webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');
