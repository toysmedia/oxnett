<?php

use App\Http\Controllers\Auth\TenantAdminLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Portal Routes
|--------------------------------------------------------------------------
|
| Routes for the PPPoE customer self-service portal.
| Accessed at {subdomain}.oxnet.co.ke/customer/
|
| Middleware:
|   - resolve.tenant  : Identifies the tenant from the subdomain
|   - subscription    : Ensures the tenant subscription is active
|   - auth:customer   : Requires an authenticated PPPoE customer
|
*/

use App\Http\Controllers\Auth\CustomerLoginController;

// Unauthenticated customer routes
Route::middleware(['resolve.tenant', 'subscription'])->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [CustomerLoginController::class, 'showLoginForm'])
            ->name('customer.login');
        Route::post('/login', [CustomerLoginController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('customer.login.submit');
    });
});

// Authenticated customer routes
Route::middleware(['resolve.tenant', 'subscription', 'auth:customer'])->group(function () {
    Route::post('/logout', [CustomerLoginController::class, 'logout'])
        ->name('customer.logout');

    Route::get('/dashboard', fn () => view('customer.dashboard'))->name('customer.dashboard');
    Route::get('/payments', fn () => view('customer.payments'))->name('customer.payments');
    Route::get('/tickets', fn () => view('customer.tickets'))->name('customer.tickets');
    Route::get('/profile', fn () => view('customer.profile'))->name('customer.profile');
});
