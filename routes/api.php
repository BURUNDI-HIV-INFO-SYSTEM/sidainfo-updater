<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/site-install-status', [App\Http\Controllers\Api\SiteController::class, 'installStatus']);
