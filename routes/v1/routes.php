<?php

use App\Http\Controllers\v1\media\UploadController;
use App\Http\Controllers\v1\ModelController;
use App\Http\Controllers\v1\TokenController;
use Illuminate\Support\Facades\Route;

Route::name('.token')->prefix('token')->controller(TokenController::class)->group(function () {
    Route::post('/issue', 'issue')->name('.issue');
});

Route::name('.model')->prefix('model')->controller(
    ModelController::class
)->group(function () {
    Route::post('/generate', 'generate')->name('.generate');
    Route::post('/webhook/{provider}/{task_id}', 'webhook')->name('.webhook');
});

Route::name('.media')->prefix('media')->group(function () {
    Route::name('.upload')->prefix('upload')->controller(
        UploadController::class
    )->group(function () {
        Route::post('/temporary', 'temporary')->name('.temporary');
    });
});
