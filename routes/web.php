<?php

use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminSetupController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [AdminLoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// 仅在 users 表为空时可访问，用于创建首个管理员账号
Route::get('/admin/setup', [AdminSetupController::class, 'showSetup'])->name('admin.setup');
Route::post('/admin/setup', [AdminSetupController::class, 'setup']);
