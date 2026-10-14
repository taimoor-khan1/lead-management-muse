<?php

use App\Http\Controllers\Proposal\ProposalController;
use App\Http\Controllers\Proposal\PublicProposalController;
use App\Http\Controllers\Settings\ServiceController;
use App\Http\Controllers\Settings\SettingController;
use Illuminate\Support\Facades\Route;

// Public proposal links — no auth required.
Route::get('/p/{token}', [PublicProposalController::class, 'show'])->name('proposals.public');
Route::post('/p/{token}/accept', [PublicProposalController::class, 'accept'])->name('proposals.accept');
Route::post('/p/{token}/decline', [PublicProposalController::class, 'decline'])->name('proposals.decline');

Route::middleware('auth')->group(function () {
    Route::post('/proposals/{proposal}/send', [ProposalController::class, 'send'])->name('proposals.send');
    Route::resource('proposals', ProposalController::class);

    Route::resource('services', ServiceController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});
