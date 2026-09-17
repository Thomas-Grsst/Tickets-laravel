<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'tickets.index' : 'login'));

Route::get('/login', fn () => view('auth.login'))->middleware('guest')->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials, remember: true)) {
        throw ValidationException::withMessages([
            'email' => __('tickets::messages.auth.failed'),
        ]);
    }

    $request->session()->regenerate();

    return redirect()->intended(route('tickets.index'));
})->middleware('guest');

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
