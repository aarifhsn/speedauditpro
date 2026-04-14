<?php

use App\Http\Controllers\AnalyzerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AnalyzerController::class, 'index'])->name('index');
Route::post('/analyze', [AnalyzerController::class, 'analyze'])->name('analyze');
Route::get('/report/{report:slug}', [AnalyzerController::class, 'show'])->name('report.show');
