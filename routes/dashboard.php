<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pipeline\PipelineController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    Route::post('/pipeline/move', [PipelineController::class, 'move'])->name('pipeline.move');
});
