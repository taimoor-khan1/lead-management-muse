<?php

namespace App\Http\Controllers\Inbox;

use App\Http\Controllers\Controller;
use App\Models\EmailSend;
use App\Models\WhatsappMessage;
use Inertia\Inertia;

class InboxController extends Controller
{
    public function index()
    {
        $emails = EmailSend::with(['lead:id,business_name,contact_person'])
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($e) => array_merge(
                $e->only('id', 'subject', 'status', 'sent_at', 'opened_at', 'created_at'),
                ['lead' => $e->lead ? $e->lead->only('id', 'business_name', 'contact_person') : null]
            ));

        $whatsapps = WhatsappMessage::with(['lead:id,business_name,contact_person'])
            ->latest()
            ->take(50)
            ->get()
            ->map(fn ($m) => array_merge(
                $m->only('id', 'direction', 'body', 'status', 'phone', 'created_at'),
                ['lead' => $m->lead ? $m->lead->only('id', 'business_name', 'contact_person') : null]
            ));

        return Inertia::render('Inbox/Inbox', [
            'emails' => $emails,
            'whatsapps' => $whatsapps,
            'counts' => [
                'emails' => EmailSend::count(),
                'whatsapp' => WhatsappMessage::count(),
            ],
        ]);
    }

    /**
     * Manual placeholder for marking an email as replied (until real reply
     * detection is wired up). POST emails.mark-replied.
     */
    public function markReplied(EmailSend $emailSend)
    {
        $emailSend->update(['status' => 'replied']);

        return back()->with('status', 'Marked as replied.');
    }
}
