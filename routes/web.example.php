<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PembayaranController;
use Illuminate\Support\Facades\Route;

/**
 * Web routes with security middleware.
 *
 * All POST/PUT/PATCH/DELETE routes are protected by CSRF by default.
 * Login routes are rate-limited to 5 attempts per minute per IP.
 */

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('login.rate.limit')
        ->name('login.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Example protected routes for payment processing
Route::middleware('auth')->group(function () {
    Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
    Route::put('/pembayaran/{pembayaran}', [PembayaranController::class, 'update'])->name('pembayaran.update');
});
