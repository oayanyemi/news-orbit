<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\PreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/filters', [ArticleController::class, 'filters']);
Route::get('/top-stories/{section}.json', [ArticleController::class, 'topStories']);

Route::prefix('/preferences/{clientId}')->group(function (): void {
    Route::get('/', [PreferenceController::class, 'show']);
    Route::put('/', [PreferenceController::class, 'update']);
});
