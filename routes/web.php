<?php

use App\Http\Controllers\AssessmentAnswerController;
use App\Http\Controllers\AssessmentSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'role:student'])->group(function (): void {
    Route::get('/profile/results', [ResultController::class, 'index'])
        ->name('profile.results.index');

    Route::get('/results/{result}', [ResultController::class, 'show'])
        ->name('results.show');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::get('/assessment', [AssessmentSessionController::class, 'intro'])
        ->name('assessment.intro');

    Route::post('/assessment/sessions', [AssessmentSessionController::class, 'store'])
        ->name('assessment.sessions.store');

    Route::get('/assessment/sessions/{assessmentSession}', [AssessmentSessionController::class, 'show'])
        ->name('assessment.show');

    Route::put('/assessment/sessions/{assessmentSession}/answers/{question}', [AssessmentAnswerController::class, 'update'])
        ->name('assessment.answers.update');

    Route::post('/assessment/sessions/{assessmentSession}/complete', [AssessmentSessionController::class, 'complete'])
        ->name('assessment.sessions.complete');
});
require __DIR__.'/auth.php';
