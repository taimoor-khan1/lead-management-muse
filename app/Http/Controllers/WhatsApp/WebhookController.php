<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * PUBLIC. Meta webhook verification handshake.
     * GET /webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=...&hub.challenge=...
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        // NB: PHP/Symfony parse query-string dots as underscores, so Meta's
        // hub.mode / hub.verify_token / hub.challenge arrive as hub_mode etc.
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        if ($mode === 'subscribe' && $verifyToken && $token === $verifyToken && $challenge !== null) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        abort(403);
    }

    /**
     * PUBLIC. Receives Meta WhatsApp webhook events.
     * POST /webhooks/whatsapp — always returns 200 so Meta keeps delivering.
     */
    public function receive(Request $request)
    {
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $this->handleIncomingMessage($message);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->handleStatusUpdate($status);
                }
            }
        }

        return response('OK', 200);
    }

    protected function handleIncomingMessage(array $message): void
    {
        $from = $message['from'] ?? null;
        if (! $from) {
            return;
        }

        $body = match ($message['type'] ?? null) {
            'text' => $message['text']['body'] ?? null,
            'button' => $message['button']['text'] ?? null,
            'interactive' => $message['interactive']['button_reply']['title']
                ?? $message['interactive']['list_reply']['title']
                ?? null,
            default => null,
        };

        $lead = Lead::firstOrCreate(
            ['phone' => $from],
            [
                'business_name' => 'WhatsApp +'.$from,
                'source' => 'whatsapp',
                'stage' => 'new',
            ]
        );

        $lead->update(['last_contacted_at' => now()]);

        WhatsappMessage::create([
            'lead_id' => $lead->id,
            'phone' => $from,
            'direction' => 'in',
            'body' => $body,
            'wa_message_id' => $message['id'] ?? null,
            'status' => 'received',
        ]);
    }

    protected function handleStatusUpdate(array $status): void
    {
        $waId = $status['id'] ?? null;
        if (! $waId) {
            return;
        }

        WhatsappMessage::where('wa_message_id', $waId)
            ->update(['status' => $status['status'] ?? 'sent']);
    }
}
