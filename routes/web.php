<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Import;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::redirect('/', 'login');

    Route::get('/register', [RegisterController::class, 'showRegisterForm']);
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showLoginForm']);
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/imports', function () {
        return Inertia::render('Imports', [
            'imports' => Import::latest('id')->get()
        ]);
    })->name('Imports');
    Route::post('/logout',[LoginController::class, 'logout']);
});

