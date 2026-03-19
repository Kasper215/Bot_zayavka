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

Route::post('/public/submit-form', [\App\Http\Controllers\PublicLeadController::class, 'submitForm']);

Route::post('/public/ai/generate-intro', [\App\Http\Controllers\AIController::class, 'generateIntro']);

Route::get('/public/me', function (Request $request) {
    return $request->user() ?: response()->json(null);
});
