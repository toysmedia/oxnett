<?php

use App\Http\Controllers\SuperAdmin\SuperAdminAuthController;
use App\Http\Controllers\SuperAdmin\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\SuperAdminTenantController;
use App\Http\Controllers\SuperAdmin\SuperAdminSubscriptionController;
use App\Http\Controllers\SuperAdmin\SuperAdminPricingPlanController;
use App\Http\Controllers\SuperAdmin\SuperAdminCmsController;
use App\Http\Controllers\SuperAdmin\SuperAdminSmsGatewayController;
use App\Http\Controllers\SuperAdmin\SuperAdminEmailGatewayController;
use App\Http\Controllers\SuperAdmin\SuperAdminAuditLogController;
use App\Http\Controllers\SuperAdmin\SuperAdminRecycleBinController;
use App\Http\Controllers\SuperAdmin\SuperAdminTenantMapController;
use Illuminate\Support\Facades\Route;

Route::prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('login', [SuperAdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [SuperAdminAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:super_admin')->group(function () {
        Route::post('logout', [SuperAdminAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('tenants', SuperAdminTenantController::class);
        Route::post('tenants/{tenant}/suspend', [SuperAdminTenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [SuperAdminTenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/impersonate', [SuperAdminTenantController::class, 'impersonate'])->name('tenants.impersonate');
        Route::post('tenants/{tenant}/maintenance', [SuperAdminTenantController::class, 'toggleMaintenance'])->name('tenants.maintenance');

        Route::resource('subscriptions', SuperAdminSubscriptionController::class)->only(['index', 'show']);
        Route::post('subscriptions/stk-push', [SuperAdminSubscriptionController::class, 'stkPush'])->name('subscriptions.stk-push');
        Route::post('subscriptions/{subscription}/extend', [SuperAdminSubscriptionController::class, 'extend'])->name('subscriptions.extend');

        Route::resource('pricing-plans', SuperAdminPricingPlanController::class);

        Route::get('cms', [SuperAdminCmsController::class, 'index'])->name('cms.index');
        Route::put('cms', [SuperAdminCmsController::class, 'update'])->name('cms.update');

        Route::get('sms-gateway', [SuperAdminSmsGatewayController::class, 'index'])->name('sms-gateway.index');
        Route::put('sms-gateway', [SuperAdminSmsGatewayController::class, 'update'])->name('sms-gateway.update');
        Route::post('sms-gateway/test', [SuperAdminSmsGatewayController::class, 'testSend'])->name('sms-gateway.test');
        Route::post('sms-gateway/campaign', [SuperAdminSmsGatewayController::class, 'sendCampaign'])->name('sms-gateway.campaign');

        Route::get('email-gateway', [SuperAdminEmailGatewayController::class, 'index'])->name('email-gateway.index');
        Route::put('email-gateway', [SuperAdminEmailGatewayController::class, 'update'])->name('email-gateway.update');
        Route::post('email-gateway/test', [SuperAdminEmailGatewayController::class, 'testSend'])->name('email-gateway.test');

        Route::get('audit-logs', [SuperAdminAuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('recycle-bin', [SuperAdminRecycleBinController::class, 'index'])->name('recycle-bin.index');
        Route::post('recycle-bin/{id}/restore', [SuperAdminRecycleBinController::class, 'restore'])->name('recycle-bin.restore');
        Route::delete('recycle-bin/{id}', [SuperAdminRecycleBinController::class, 'destroy'])->name('recycle-bin.destroy');

        Route::get('tenant-map', [SuperAdminTenantMapController::class, 'index'])->name('tenant-map.index');
    });
});
