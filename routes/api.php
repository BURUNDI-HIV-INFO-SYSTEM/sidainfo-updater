<?php

use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::post('/site-install-status', [SiteController::class, 'installStatus']);
