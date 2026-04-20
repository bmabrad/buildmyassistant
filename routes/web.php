<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\Impersonating;
use App\Livewire\LaunchpadChat;
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

// Launchpad chat
Route::get('/launchpad', LaunchpadChat::class)->name('launchpad');

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
