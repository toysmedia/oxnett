<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\ActivateVoucherController;
use App\Http\Controllers\Api\PackageController as ApiPackageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// M-Pesa Webhooks (no CSRF, no auth required)
Route::post('/mpesa/stk-push', [MpesaController::class, 'stkPush']);
Route::post('/mpesa/stk-callback', [MpesaController::class, 'stkCallback']);
Route::post('/mpesa/c2b-validation', [MpesaController::class, 'c2bValidation']);
Route::post('/mpesa/c2b-confirmation', [MpesaController::class, 'c2bConfirmation']);
Route::get('/mpesa/register-c2b', [MpesaController::class, 'registerC2BUrls']);

// Voucher activation
Route::post('/activate-mpesa-voucher', [ActivateVoucherController::class, 'activate']);

// Package listing
Route::get('/packages', [ApiPackageController::class, 'index']);

// Check payment status
Route::get('/check-payment/{ref}', [MpesaController::class, 'checkPayment']);

// Router auto-registration callback (called from MikroTik script via /tool fetch)
use App\Http\Controllers\Api\RouterCallbackController;
Route::post('/router-callback', [RouterCallbackController::class, 'callback']);