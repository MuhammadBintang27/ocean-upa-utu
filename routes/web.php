<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\PassageController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route(auth()->user()->is_admin ? 'admin.dashboard' : 'exam'))->name('home');

    Route::middleware('participant')->prefix('exam')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('exam');
        Route::get('/status', [ExamController::class, 'status'])->name('exam.status');
        Route::post('/start',[ExamController::class, 'start'])->name('exam.start');
        Route::post('/answer', [ExamController::class, 'answer'])->name('exam.answer');
        Route::post('/finish', [ExamController::class, 'finish'])->name('exam.finish');
        Route::post('/violation', [ExamController::class, 'violation'])->name('exam.violation');
        Route::post('/avatar', [ExamController::class, 'avatar'])->name('exam.avatar');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/export', [DashboardController::class, 'export'])->name('export');
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::put('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
        Route::post('/toggle-open', [DashboardController::class, 'toggleOpen'])->name('toggle-open');
        Route::get('/lobby', [DashboardController::class, 'lobby'])->name('lobby');
        Route::get('/lobby/data', [DashboardController::class, 'lobbyData'])->name('lobby.data');
        Route::post('/open', [DashboardController::class, 'open'])->name('open');

        Route::post('/participants/import', [ParticipantController::class, 'import'])->name('participants.import');
        Route::get('/participants/template', [ParticipantController::class, 'template'])->name('participants.template');
        Route::get('/participants/cards', [ParticipantController::class, 'cards'])->name('participants.cards');
        Route::get('/participants/accounts',[ParticipantController::class, 'exportAccounts'])->name('participants.accounts');
        Route::post('/participants/{participant}/reset-device', [ParticipantController::class, 'resetDevice'])->name('participants.reset-device');
        Route::post('/participants/{participant}/reset-exam', [ParticipantController::class, 'resetExam'])->name('participants.reset-exam');
        Route::resource('participants', ParticipantController::class)->except(['create']);

        Route::resource('questions', QuestionController::class)->except(['show']);
        Route::resource('passages', PassageController::class)->except(['index', 'show']);
    });
});
