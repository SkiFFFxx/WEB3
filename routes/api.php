<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\QuestionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Создание пользователя
// Я написал это в код для коммита new
Route::post('/users', [ApiController::class, 'createUser']);

// Получение списка пользователей
Route::get('/users', [ApiController::class, 'getUsers']);

// Обновление пользователя
Route::patch('/users/{id}', [ApiController::class, 'updateUser']);

// Удаление пользователя
Route::delete('/users/{id}', [ApiController::class, 'deleteUser']);



Route::post('/create-question', [QuestionController::class, 'createQuestion']);

Route::get('/questions', [QuestionController::class, 'getAllQuestions']);

Route::patch('/questions/{id}', [QuestionController::class, 'updateQuestion']);

Route::delete('/questions/{id}', [QuestionController::class, 'deleteQuestion']);



Route::post('/quiz/check-answers', [QuestionController::class, 'checkAnswers']);

#test
