<?php

use App\Http\Controllers\Admin\AssessmentVersionController as AdminAssessmentVersionController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\StatisticsController as AdminStatisticsController;
use App\Http\Controllers\AssessmentAnswerController;
use App\Http\Controllers\AssessmentSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\SpecializationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/specializations', [SpecializationController::class, 'index'])
    ->name('specializations.index');

Route::get('/specializations/compare', [SpecializationController::class, 'compare'])
    ->name('specializations.compare');

Route::get('/specializations/{specialization}', [SpecializationController::class, 'show'])
    ->name('specializations.show');

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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function (): void {
    Route::get('/assessment-versions', [AdminAssessmentVersionController::class, 'index'])
        ->name('assessment-versions.index');
    Route::get('/assessment-versions/{assessmentVersion}', [AdminAssessmentVersionController::class, 'show'])
        ->whereNumber('assessmentVersion')
        ->name('assessment-versions.show');
    Route::post('/assessment-versions', [AdminAssessmentVersionController::class, 'store'])
        ->name('assessment-versions.store');
    Route::post('/assessment-versions/{assessmentVersion}/questions', [AdminQuestionController::class, 'store'])
        ->name('assessment-versions.questions.store');
    Route::get('/assessment-versions/{assessmentVersion}/questions/create', [AdminQuestionController::class, 'create'])
        ->whereNumber('assessmentVersion')
        ->name('assessment-versions.questions.create');
    Route::get('/assessment-versions/{assessmentVersion}/questions/{question}/edit', [AdminQuestionController::class, 'edit'])
        ->whereNumber('assessmentVersion')
        ->name('assessment-versions.questions.edit');
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
