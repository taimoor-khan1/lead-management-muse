<?php

use App\Http\Controllers\Inbox\InboxController;
use App\Http\Controllers\WhatsApp\WebhookController;
use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Support\Facades\Route;

// PUBLIC — Meta WhatsApp webhook (no auth)
Route::get('/webhooks/whatsapp', [WebhookController::class, 'verify'])->name('webhooks.whatsapp');
Route::post('/webhooks/whatsapp', [WebhookController::class, 'receive']);

Route::middleware(['auth', 'verified'])->group(function () {
    // WhatsApp threads
    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/{lead}', [WhatsAppController::class, 'show'])->name('whatsapp.thread');
    Route::post('/whatsapp/send', [WhatsAppController::class, 'send'])->name('whatsapp.send');

    // Unified inbox
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/emails/{emailSend}/mark-replied', [InboxController::class, 'markReplied'])
        ->name('emails.mark-replied');
});
