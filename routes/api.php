<?php

use App\Http\Controllers\Api\ExamensController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\TarifsController;
use Illuminate\Support\Facades\Route;

Route::post('/site-install-status', [SiteController::class, 'installStatus']);

// Configuration APIs — consumed by remote SIDAInfo sites
Route::get('/tarifs',  [TarifsController::class,  'index']);
Route::get('/examens', [ExamensController::class, 'index']);
