<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\VillageDashboardController;
use App\Http\Controllers\Dashboard\UserDashboardController;
use App\Http\Controllers\PembayaranController;
use Illuminate\Support\Facades\Route;

/**
 * Root redirect: Send unauthenticated users to login
 */
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

/**
 * Authentication Routes (Guest only)
 */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('login.rate.limit')
        ->name('login.post');
});

/**
 * Authenticated Routes
 */
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /**
     * Dashboard Routes: Redirect based on role
     */
    Route::get('/dashboard', function () {
        return match (auth()->user()->role) {
            'super_admin' => redirect()->route('admin.dashboard'),
            'kades', 'kasun_rw', 'rt' => redirect()->route('village.dashboard'),
            'pengguna' => redirect()->route('user.dashboard'),
        };
    })->name('dashboard');

    /**
     * Super Admin Dashboard (Full CRUD access)
     */
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
            ->name('admin.dashboard');
        Route::post('/admin/import', [AdminDashboardController::class, 'import'])
            ->name('admin.import');
        Route::get('/admin/import-status/{jobId}', [AdminDashboardController::class, 'importStatus'])
            ->name('admin.import.status');
        Route::get('/admin/import-template/{module}', [AdminDashboardController::class, 'downloadTemplate'])
            ->name('admin.import.template');
        Route::get('/admin/export', [AdminDashboardController::class, 'exportPage'])
            ->name('admin.export');
        Route::post('/admin/export', [AdminDashboardController::class, 'export'])
            ->name('admin.export.submit');
        Route::get('/admin/export-status/{jobId}', [AdminDashboardController::class, 'exportStatus'])
            ->name('admin.export.status');
        Route::get('/admin/export-download/{jobId}', [AdminDashboardController::class, 'downloadExport'])
            ->name('admin.export.download');
        Route::post('/admin/approve-payment/{pembayaran}', [AdminDashboardController::class, 'approvePayment'])
            ->name('admin.approve-payment');
    });

    Route::middleware('role:super_admin,kades,kasun_rw,rt')->group(function () {
        Route::get('/village/payments', [VillageDashboardController::class, 'payments'])
            ->name('village.payments');
        Route::post('/village/payments/batch', [PembayaranController::class, 'batchStore'])
            ->name('village.payments.batch');
    });

    Route::get('/api/search', [\App\Http\Controllers\SearchController::class, 'search'])
        ->name('api.search');

    /**
     * Village Dashboard (Kades, Kasun RW, RT - View and Filter)
     */
    Route::middleware('role:kades,kasun_rw,rt')->group(function () {
        Route::get('/village/dashboard', [VillageDashboardController::class, 'index'])
            ->name('village.dashboard');
        Route::get('/village/statistics', [VillageDashboardController::class, 'statistics'])
            ->name('village.statistics');
    });

    /**
     * User Dashboard (Regular Users - View own SPPTs and submit proposals)
     */
    Route::get('/user/dashboard', [UserDashboardController::class, 'index'])
        ->name('user.dashboard');
    Route::get('/user/sppt', [UserDashboardController::class, 'mySppt'])
        ->name('user.sppt');
    Route::post('/user/sppt/{sppt}/payment-proposal', [UserDashboardController::class, 'submitPaymentProposal'])
        ->name('user.payment-proposal');

    /**
     * Payment Routes (Super Admin only - Direct CRUD)
     */
    Route::middleware('role:super_admin')->group(function () {
        Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
        Route::put('/pembayaran/{pembayaran}', [PembayaranController::class, 'update'])->name('pembayaran.update');
        Route::delete('/pembayaran/{pembayaran}', [PembayaranController::class, 'destroy'])->name('pembayaran.destroy');
    });
});
