<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MonitorController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('monitors')->group(function () {
    Route::post('/', [MonitorController::class, 'store']);
    Route::get('/', [MonitorController::class, 'index']);
    Route::get('/{monitor}/history', [MonitorController::class, 'history']);
});
