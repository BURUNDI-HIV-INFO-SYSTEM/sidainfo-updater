<?php

use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\SiteAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\UpdateController;
use Illuminate\Support\Facades\Route;

// ── Public update endpoints (used by site instances) ─────────────────────────
Route::get('/laraupdater.json', [UpdateController::class, 'metadata'])->name('update.metadata');
Route::get('/RELEASE-{version}.zip', [UpdateController::class, 'download'])->name('update.download')
    ->where('version', '[0-9a-zA-Z._-]+');

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Admin (requires authentication) ──────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Releases
    Route::get('/releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::get('/releases/new', [ReleaseController::class, 'create'])->name('releases.create');
    Route::post('/releases', [ReleaseController::class, 'store'])->name('releases.store');
    Route::get('/releases/{release}', [ReleaseController::class, 'show'])->name('releases.show');
    Route::post('/releases/{release}/activate', [ReleaseController::class, 'activate'])->name('releases.activate');
    Route::delete('/releases/{release}', [ReleaseController::class, 'destroy'])->name('releases.destroy');

    // Sites
    Route::get('/sites', [SiteAdminController::class, 'index'])->name('sites.index');
    Route::get('/sites/{site}', [SiteAdminController::class, 'show'])->name('sites.show');
});
