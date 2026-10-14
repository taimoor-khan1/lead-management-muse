<?php

use App\Http\Controllers\Automation\AutomationController;
use App\Http\Controllers\Lead\LeadNoteController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Task\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::post('/leads/{lead}/notes', [LeadNoteController::class, 'store'])->name('leads.notes.store');
    Route::delete('/leads/notes/{note}', [LeadNoteController::class, 'destroy'])->name('leads.notes.destroy');

    Route::get('/automations', [AutomationController::class, 'index'])->name('automations.index');
    Route::post('/automations', [AutomationController::class, 'store'])->name('automations.store');
    Route::patch('/automations/{automation}', [AutomationController::class, 'update'])->name('automations.update');
    Route::delete('/automations/{automation}', [AutomationController::class, 'destroy'])->name('automations.destroy');
    Route::post('/automations/run', [AutomationController::class, 'run'])->name('automations.run');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});
