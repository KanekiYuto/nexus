<?php

use App\Http\Controllers\v1\media\UploadController;
use App\Http\Controllers\v1\ModelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['app.token'])->group(function () {
    Route::name('.model')->prefix('model')->controller(ModelController::class)->group(function () {
        Route::post('/generate', 'generate')->name('.generate');
    });

    Route::name('.media')->prefix('media')->group(function () {
        Route::name('.upload')->prefix('upload')->controller(UploadController::class)->group(function () {
            Route::post('/temporary', 'temporary')->name('.temporary');
        });
    });
});

Route::name('.model')->prefix('model')->controller(ModelController::class)->group(function () {
    Route::post('/webhook/{provider}/{task_id}', 'webhook')->name('.webhook');
});
