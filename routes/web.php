<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [FinanceController::class, 'index'])->name('dashboard');
    Route::get('/finance/initial', [FinanceController::class, 'showInitialInput'])->name('finance.initial');
    Route::post('/finance/initial', [FinanceController::class, 'storeInitialInput'])->name('finance.store');
    Route::get('/finance/recap', [FinanceController::class, 'showRecap'])->name('finance.recap');
    Route::get('/finance/report/pnl', [FinanceController::class, 'reportPnL'])->name('finance.report.pnl');
    Route::get('/finance/report/log', [FinanceController::class, 'reportLog'])->name('finance.report.log');
    Route::get('/finance/report/insight', [FinanceController::class, 'reportInsight'])->name('finance.report.insight');
    
    // Cash In Routes
    // Cash In Routes
    Route::get('/finance/cash-in', [FinanceController::class, 'cashInIndex'])->name('finance.cash-in.index');
    Route::get('/finance/cash-in/create', [FinanceController::class, 'cashInCreate'])->name('finance.cash-in.create');
    Route::post('/finance/cash-in', [FinanceController::class, 'cashInStore'])->name('finance.cash-in.store');
    Route::get('/finance/cash-in/{id}/edit', [FinanceController::class, 'cashInEdit'])->name('finance.cash-in.edit');
    Route::put('/finance/cash-in/{id}', [FinanceController::class, 'cashInUpdate'])->name('finance.cash-in.update');
    Route::delete('/finance/cash-in/{id}', [FinanceController::class, 'cashInDestroy'])->name('finance.cash-in.destroy');
    Route::get('/finance/cash-in/analysis', [FinanceController::class, 'salesAnalysis'])->name('finance.cash-in.analysis');

    // Cash Out Routes
    Route::get('/finance/cash-out', [FinanceController::class, 'cashOutIndex'])->name('finance.cash-out.index');
    Route::get('/finance/cash-out/create', [FinanceController::class, 'cashOutCreate'])->name('finance.cash-out.create');
    Route::post('/finance/cash-out', [FinanceController::class, 'cashOutStore'])->name('finance.cash-out.store');
    Route::get('/finance/cash-out/{id}/edit', [FinanceController::class, 'cashOutEdit'])->name('finance.cash-out.edit');
    Route::put('/finance/cash-out/{id}', [FinanceController::class, 'cashOutUpdate'])->name('finance.cash-out.update');
    Route::delete('/finance/cash-out/{id}', [FinanceController::class, 'cashOutDestroy'])->name('finance.cash-out.destroy');
    Route::get('/finance/cash-out/analysis', [FinanceController::class, 'expenseAnalysis'])->name('finance.cash-out.analysis');
    Route::get('/profile', [FinanceController::class, 'profile'])->name('profile');
    Route::post('/profile', [FinanceController::class, 'updateProfile'])->name('profile.update');
    
    // AI Routes
    Route::post('/ai/chat', [App\Http\Controllers\AIController::class, 'chat'])->name('ai.chat');
    
    // AI Analyst Routes
    Route::get('/ai-analyst/data', [App\Http\Controllers\AiAnalysisController::class, 'getAnalysis'])->name('ai.analyst.data');
    Route::get('/ai-analyst/report', [App\Http\Controllers\AiAnalysisController::class, 'getReport'])->name('ai.analyst.report');
    Route::get('/ai-analyst/strategy', [App\Http\Controllers\AiAnalysisController::class, 'getStrategy'])->name('ai.analyst.strategy');
    Route::post('/ai-analyst/strategy/regenerate', [App\Http\Controllers\AiAnalysisController::class, 'regenerateStrategy'])->name('ai.analyst.regenerate');
    Route::post('/ai-analyst/strategy/accept', [App\Http\Controllers\AiAnalysisController::class, 'acceptStrategy'])->name('ai.analyst.accept');
});
