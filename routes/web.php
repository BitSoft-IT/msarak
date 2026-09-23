<?php

use App\Http\Controllers\Admin\AssessmentVersionController as AdminAssessmentVersionController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\StatisticsController as AdminStatisticsController;
use App\Http\Controllers\AssessmentAnswerController;
use App\Http\Controllers\AssessmentSessionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth', 'role:student'])->group(function (): void {
    Route::post('/assessment/sessions', [AssessmentSessionController::class, 'store'])
        ->name('assessment.sessions.store');

    Route::get('/assessment/sessions/{assessmentSession}', [AssessmentSessionController::class, 'show'])
        ->name('assessment.show');

    Route::put('/assessment/sessions/{assessmentSession}/answers/{question}', [AssessmentAnswerController::class, 'update'])
        ->name('assessment.answers.update');

    Route::post('/assessment/sessions/{assessmentSession}/complete', [AssessmentSessionController::class, 'complete'])
        ->name('assessment.sessions.complete');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function (): void {
    Route::post('/assessment-versions', [AdminAssessmentVersionController::class, 'store'])
        ->name('assessment-versions.store');
    Route::post('/assessment-versions/{assessmentVersion}/questions', [AdminQuestionController::class, 'store'])
        ->name('assessment-versions.questions.store');
    Route::put('/assessment-versions/{assessmentVersion}/questions/{question}', [AdminQuestionController::class, 'update'])
        ->name('assessment-versions.questions.update');
    Route::delete('/assessment-versions/{assessmentVersion}/questions/{question}', [AdminQuestionController::class, 'destroy'])
        ->name('assessment-versions.questions.destroy');
    Route::post('/assessment-versions/{assessmentVersion}/publish', [AdminAssessmentVersionController::class, 'publish'])
        ->name('assessment-versions.publish');
    Route::get('/statistics', [AdminStatisticsController::class, 'index'])
        ->name('statistics.index');
});
require __DIR__.'/auth.php';
