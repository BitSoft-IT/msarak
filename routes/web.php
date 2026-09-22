<?php

use App\Http\Controllers\AssessmentAnswerController;
use App\Http\Controllers\AssessmentSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'role:student'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::post('/assessment/sessions', [AssessmentSessionController::class, 'store'])
        ->name('assessment.sessions.store');

    Route::get('/assessment/sessions/{assessmentSession}', [AssessmentSessionController::class, 'show'])
        ->name('assessment.show');

    Route::put('/assessment/sessions/{assessmentSession}/answers/{question}', [AssessmentAnswerController::class, 'update'])
        ->name('assessment.answers.update');
});
require __DIR__.'/auth.php';
