<?php

use App\Http\Controllers\Admin\AppController;
use App\Http\Controllers\Admin\AppTokenController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\SetupController;
use Illuminate\Support\Facades\Route;

// 初始化（仅在 users 表为空时可用）
Route::get('/admin/setup', [SetupController::class, 'showSetup'])->name('admin.setup');
Route::post('/admin/setup', [SetupController::class, 'setup']);

// 登录 / 登出
Route::get('/admin/login', [LoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'login']);
Route::post('/admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// 需要登录的管理路由
Route::middleware('auth')->prefix('admin')->group(function () {

    // 仪表盘默认跳转应用列表
    Route::get('/', fn () => redirect('/admin/apps'));

    // 应用管理
    Route::get('/apps', [AppController::class, 'index'])->name('admin.apps.index');
    Route::get('/apps/create', [AppController::class, 'create'])->name('admin.apps.create');
    Route::post('/apps', [AppController::class, 'store'])->name('admin.apps.store');
    Route::get('/apps/{app}/edit', [AppController::class, 'edit'])->name('admin.apps.edit');
    Route::put('/apps/{app}', [AppController::class, 'update'])->name('admin.apps.update');
    Route::patch('/apps/{app}/toggle-status', [AppController::class, 'toggleStatus'])->name('admin.apps.toggle-status');

    // Token 管理
    Route::get('/apps/{app}/tokens', [AppTokenController::class, 'index'])->name('admin.apps.tokens.index');
    Route::post('/apps/{app}/tokens', [AppTokenController::class, 'store'])->name('admin.apps.tokens.store');
    Route::delete('/apps/{app}/tokens/{token}', [AppTokenController::class, 'destroy'])->name('admin.apps.tokens.destroy');
});
