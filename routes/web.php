<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\ExamensConfigController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\SiteAdminController;
use App\Http\Controllers\Admin\TarifsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

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

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // Backups
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('/backups/new', [BackupController::class, 'create'])->name('backups.create');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{backupDestination}/edit', [BackupController::class, 'edit'])->name('backups.edit');
    Route::put('/backups/{backupDestination}', [BackupController::class, 'update'])->name('backups.update');
    Route::post('/backups/{backupDestination}/run', [BackupController::class, 'run'])->name('backups.run');

    // Tarifs des examens
    Route::get('/tarifs', [TarifsController::class, 'index'])->name('tarifs.index');
    Route::post('/tarifs', [TarifsController::class, 'upsert'])->name('tarifs.upsert');
    Route::get('/tarifs/template', [TarifsController::class, 'downloadTemplate'])->name('tarifs.template');
    Route::post('/tarifs/import', [TarifsController::class, 'import'])->name('tarifs.import');

    // Configuration des examens
    Route::get('/examens-config', [ExamensConfigController::class, 'index'])->name('examens-config.index');
    Route::post('/examens-config', [ExamensConfigController::class, 'bulkUpdate'])->name('examens-config.update');
    Route::get('/examens-config/template', [ExamensConfigController::class, 'downloadTemplate'])->name('examens-config.template');
    Route::post('/examens-config/import', [ExamensConfigController::class, 'import'])->name('examens-config.import');
});
