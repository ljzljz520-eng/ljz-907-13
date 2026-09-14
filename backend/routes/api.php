<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ScreeningController;

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

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::delete('/movies/{id}', [MovieController::class, 'destroy']);
Route::post('/upload', [MovieController::class, 'upload']);
Route::get('/proxy-image', [MovieController::class, 'proxyImage']);

// 公益放映排期
Route::get('/screenings', [ScreeningController::class, 'index']);
Route::post('/screenings', [ScreeningController::class, 'store']);
Route::put('/screenings/{id}', [ScreeningController::class, 'update']);
Route::delete('/screenings/{id}', [ScreeningController::class, 'destroy']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});
