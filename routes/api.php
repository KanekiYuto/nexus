<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::name('v1.')->prefix('v1')->group(__DIR__ . '/v1/routes.php');
