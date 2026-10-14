<?php

use App\Http\Controllers\Campaign\CampaignController;
use App\Http\Controllers\Email\EmailSendController;
use App\Http\Controllers\Email\EmailTemplateController;
use App\Http\Controllers\Email\TrackController;
use Illuminate\Support\Facades\Route;

/*
 * Email outreach + campaigns routes (required by the coordinator from web.php).
 */

Route::middleware(['auth'])->group(function () {
    Route::resource('email-templates', EmailTemplateController::class);

    Route::get('/emails', [EmailSendController::class, 'index'])->name('emails.index');
    Route::get('/emails/compose', [EmailSendController::class, 'compose'])->name('emails.compose');
    Route::post('/emails/send', [EmailSendController::class, 'send'])->name('emails.send');

    Route::resource('campaigns', CampaignController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/campaigns/{campaign}/launch', [CampaignController::class, 'launch'])
        ->name('campaigns.launch');
});

// Public tracking endpoints — no auth.
Route::get('/t/o/{token}', [TrackController::class, 'open'])->name('track.open');
Route::get('/t/c/{token}', [TrackController::class, 'click'])->name('track.click');
