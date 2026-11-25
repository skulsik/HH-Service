<?php

use App\Http\Controllers\VacancyApiController;
use App\Http\Controllers\VacancyController;
use Illuminate\Support\Facades\Route;

// JSON endpoints для Vue.
Route::get('/vacancies/data', [VacancyApiController::class, 'index'])->name('vacancies.data');
Route::get('/vacancies/changes', [VacancyApiController::class, 'changes'])->name('vacancies.changes');
Route::get('/vacancies/monitor', [VacancyApiController::class, 'monitor'])->name('vacancies.monitor');
Route::post('/vacancies/{vacancy}/viewed', [VacancyApiController::class, 'markAsViewed'])
    ->name('vacancies.viewed');

Route::get('/', [VacancyController::class, 'index'])->name('vacancies.index');
Route::get('/vacancies/{vacancy}', [VacancyController::class, 'show'])->name('vacancies.show');
