<?php

use App\Http\Controllers\Api\QuestionController;
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
    Route::controller(QuestionController::class)->group(function () {
        // logged in user questions
        Route::get('user/questions', 'authUserQuestions');
        Route::post('question/store', 'store');
        Route::put('update/{question}/question', 'update');
        Route::delete('deletequestion}/question', 'destroy');
        Route::put('vote/{question}/{type}/question', 'vote');
        //Route::put('votedown/{question}/{type}/question', 'vote');
    });
});

Route::controller(UserController::class)->group(function () {
    Route::post('user/register', 'store')->name('users.register');
    Route::post('user/login', 'auth');
});
Route::controller(QuestionController::class)->group(function () {
    Route::get('questions', 'index');
    Route::get('question/{question}/show', 'show');
    Route::get('tag/{tag}/questions', 'questionByTag');
    Route::post('user/questions', 'questionsByUser');
});
