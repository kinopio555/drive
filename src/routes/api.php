<?php

use App\Http\Controllers\Api\RestaurantNearbyController;
use App\Http\Controllers\Api\RoutePolylineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->get('/restaurants-nearby', [RestaurantNearbyController::class, 'index']);
Route::middleware(['auth:sanctum'])->get('/routes/polyline', RoutePolylineController::class);
Route::middleware(['auth:sanctum'])->post('/restaurants-nearby', [RestaurantNearbyController::class, 'store']);
