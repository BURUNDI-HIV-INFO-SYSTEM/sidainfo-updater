<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/laraupdater.json', [App\Http\Controllers\Api\UpdateController::class, 'metadata']);
Route::get('/updates/{version}.zip', [App\Http\Controllers\Api\UpdateController::class, 'download']);
