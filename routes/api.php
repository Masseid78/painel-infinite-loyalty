<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class);
Route::get('/settings', [SettingController::class, 'show']);
Route::put('/settings', [SettingController::class, 'update']);

Route::apiResource('companies', CompanyController::class);

Route::get('/backup', [BackupController::class, 'download']);
Route::post('/backup/restore', [BackupController::class, 'restore']);
