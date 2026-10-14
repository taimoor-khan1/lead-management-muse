<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Lead;
use App\Support\EmailVariables;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Sends a campaign WhatsApp message to a lead.
 *
 * If App\Services\WhatsAppService exists (WhatsApp module), it is used to
 * send the message. Otherwise the lead is marked as sent in stub mode so
 * campaign stats still work in dev.
 */
class WhatsappSendJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $campaignLeadId) {}

    public function handle(): void
    {
        $row = DB::table('campaign_leads')->where('id', $this->campaignLeadId)->first();

        if (! $row || $row->status !== 'pending') {
            return;
        }

        try {
            $campaign = Campaign::find($row->campaign_id);
            $lead = $campaign ? Lead::find($row->lead_id) : null;

            if (! $campaign || ! $lead) {
                throw new \RuntimeException('Campaign or lead not found.');
            }

            if (! $lead->phone) {
                throw new \RuntimeException('Lead has no phone number.');
            }

            $body = EmailVariables::replace($campaign->whatsapp_template ?? '', $lead);

            if (class_exists(\App\Services\WhatsAppService::class)) {
                app(\App\Services\WhatsAppService::class)->send($lead->phone, $body);
                $this->mark('sent', null);
            } else {
                $this->mark('sent', 'stub: WhatsAppService not available');
            }
        } catch (\Throwable $e) {
            $this->mark('failed', $e->getMessage());
        }
    }

    protected function mark(string $status, ?string $error): void
    {
        DB::table('campaign_leads')->where('id', $this->campaignLeadId)->update([
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'error' => $error,
            'updated_at' => now(),
        ]);
    }
}
