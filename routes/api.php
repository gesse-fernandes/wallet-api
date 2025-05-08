<?php

use App\Http\Controllers\API\AuthControllerApi;
use App\Http\Controllers\API\TransactionControllerApi;
use App\Http\Middleware\DisableSwaggerCsrf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {

    Route::post('/auth/register', [AuthControllerApi::class, 'register']);


    Route::post('/auth/login', [AuthControllerApi::class, 'login']);


    Route::middleware('auth:api')->group(function () {
        Route::post('/auth/logout', [AuthControllerApi::class, 'logout']);

        // Rota de transferência
        Route::post('/transactions/transfer', [TransactionControllerApi::class, 'transfer']);
    });
});
