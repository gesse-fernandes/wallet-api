<?php

use App\Http\Controllers\API\AuthControllerApi;
use App\Http\Middleware\DisableSwaggerCsrf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::post('/auth/register', [AuthControllerApi::class, 'register']);
});
