<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout');
    });
});

Route::prefix('users')
    ->middleware(['auth:sanctum', 'section:users'])
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/export/pdf', 'exportPdf');
        Route::get('/export/excel', 'exportExcel');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::patch('/{id}/status', 'updateStatus');
    });

Route::prefix('profiles')
    ->middleware(['auth:sanctum', 'section:profiles'])
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/export/pdf', 'exportPdf');
        Route::get('/export/excel', 'exportExcel');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

Route::prefix('sections')
    ->middleware(['auth:sanctum', 'section:profiles'])
    ->controller(SectionController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('/{id}', 'destroy');
    });

Route::prefix('products')
    ->middleware(['auth:sanctum', 'section:products'])
    ->controller(ProductController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/export/pdf', 'exportPdf');
        Route::get('/export/excel', 'exportExcel');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

Route::prefix('audit-logs')
    ->middleware(['auth:sanctum', 'section:audit-logs'])
    ->controller(AuditLogController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/export/pdf', 'exportPdf');
        Route::get('/export/excel', 'exportExcel');
        Route::get('/{id}', 'show');
    });
