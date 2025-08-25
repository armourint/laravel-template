<?php

use App\Http\Middleware\IsAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Root redirect
 */
Route::get('/', fn () => Auth::check()
    ? redirect()->route('admin.alerts')
    : redirect()->route('login')
);

/**
 * Auth screens
 */
Route::get('/login', function () {
    return Auth::check()
        ? redirect()->route('admin.alerts')
        : view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        // Redirect by role
        return Auth::user()->role === 'admin'
            ? redirect()->intended(route('admin.alerts'))
            : redirect()->intended(route('admin.alerts')); // same page for engineers
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->withInput();
})->name('login.attempt')->middleware('guest');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('status', 'Logged out successfully.');
})->name('logout')->middleware('auth');

/**
 * Authenticated routes
 */
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    // ---- Accessible to BOTH admins & engineers ----
    Route::view('/alerts', 'admin.alerts')->name('alerts');

    // ---- ADMIN ONLY ----
    Route::middleware(IsAdmin::class)->group(function () {
        Route::view('/users', 'admin.users')->name('users');
        Route::view('/sites', 'admin.sites')->name('sites');
        // add other admin-only pages here
        Route::view('/agents', 'admin.agents')->name('agents');
        Route::view('/cameras', 'admin.cameras')->name('cameras');
    });
});
Route::middleware(['auth'])->group(function(){ Route::view('/','dashboard')->name('dashboard'); });
