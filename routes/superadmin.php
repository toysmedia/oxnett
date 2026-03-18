<?php

use App\Http\Controllers\Auth\SuperAdminLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
|
| Routes for the OxNet Super Admin dashboard at admin.oxnet.co.ke.
| All routes here are protected by the 'super_admin' authentication guard.
|
*/

// Authentication (unauthenticated)
Route::middleware('guest:super_admin')->group(function () {
    Route::get('/login', [SuperAdminLoginController::class, 'showLoginForm'])
        ->name('superadmin.login');
    Route::post('/login', [SuperAdminLoginController::class, 'login'])
        ->middleware('throttle:10,1');
});

// Authenticated Super Admin routes
Route::middleware('auth:super_admin')->group(function () {
    Route::post('/logout', [SuperAdminLoginController::class, 'logout'])
        ->name('superadmin.logout');

    Route::get('/dashboard', function () {
        return view('superadmin.dashboard');
    })->name('superadmin.dashboard');

    // Tenant management
    Route::prefix('tenants')->name('superadmin.tenants.')->group(function () {
        Route::get('/', fn () => view('superadmin.tenants.index'))->name('index');
        Route::get('/{tenant}', fn () => view('superadmin.tenants.show'))->name('show');
    });

    // Subscription & billing
    Route::prefix('billing')->name('superadmin.billing.')->group(function () {
        Route::get('/', fn () => view('superadmin.billing.index'))->name('index');
    });

    // Audit logs
    Route::get('/audit-logs', fn () => view('superadmin.audit-logs.index'))->name('superadmin.audit-logs.index');

    // Recycle bin
    Route::get('/recycle-bin', fn () => view('superadmin.recycle-bin.index'))->name('superadmin.recycle-bin.index');

    // CMS
    Route::prefix('cms')->name('superadmin.cms.')->group(function () {
        Route::get('/', fn () => view('superadmin.cms.index'))->name('index');
    });

    // Pricing plans
    Route::prefix('plans')->name('superadmin.plans.')->group(function () {
        Route::get('/', fn () => view('superadmin.plans.index'))->name('index');
    });
});
