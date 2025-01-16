<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return UserResource::make($request->user());
    });

    Route::controller(UserController::class)->group(function () {
        Route::post('user/logout', 'logout');
        Route::put('update/profile', 'updateUserInfo');
        Route::put('update/password', 'updateUserPassword');
    });
});

Route::controller(UserController::class)->group(function () {
    Route::post('user/register', 'store')->name('users.register');
    Route::post('user/login', 'auth');
});
