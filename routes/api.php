<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/public/payment-settings', [\App\Http\Controllers\Admin\PaymentController::class, 'getSettings']);

Route::post('/public/submit-form', [\App\Http\Controllers\PublicLeadController::class, 'submitForm']);
Route::post('/public/submit-payment', [\App\Http\Controllers\PublicPaymentController::class, 'submitPayment']);
Route::get('/public/payments/{id}/status', [\App\Http\Controllers\PublicPaymentController::class, 'paymentStatus']);

Route::post('/public/ai/generate-intro', [\App\Http\Controllers\AIController::class, 'generateIntro']);

Route::get('/public/me', function (Request $request) {
    return $request->user() ?: response()->json(null);
});
