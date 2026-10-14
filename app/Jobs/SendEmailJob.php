<?php

namespace App\Jobs;

use App\Mail\LeadOutreachMail;
use App\Models\EmailSend;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $emailSendId,
        public ?int $campaignLeadId = null,
    ) {}

    public function handle(): void
    {
        $send = EmailSend::find($this->emailSendId);

        if (! $send) {
            return;
        }

        $send->status = 'sending';
        $send->save();

        try {
            $lead = $send->lead;

            if (! $lead || ! $lead->email) {
                throw new \RuntimeException('Lead has no email address.');
            }

            Mail::to($lead->email)->send(new LeadOutreachMail(
                $send->subject,
                $this->buildHtml($send),
            ));

            $send->status = 'sent';
            $send->sent_at = now();
            $send->error = null;
            $send->save();

            $lead->last_contacted_at = now();
            $lead->save();

            $this->markCampaignLead('sent', null);
        } catch (\Throwable $e) {
            $send->status = 'failed';
            $send->error = $e->getMessage();
            $send->save();

            $this->markCampaignLead('failed', $e->getMessage());
        }
    }

    protected function markCampaignLead(string $status, ?string $error): void
    {
        if (! $this->campaignLeadId) {
            return;
        }

        DB::table('campaign_leads')->where('id', $this->campaignLeadId)->update([
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'error' => $error,
            'updated_at' => now(),
        ]);
    }

    /**
     * Converts the plain-text body to safe HTML, rewrites http(s) links
     * through the click tracker, and appends the open-tracking pixel.
     */
    protected function buildHtml(EmailSend $send): string
    {
        $openUrl = route('track.open', ['token' => $send->tracking_token]);

        // Split raw URLs out first so query strings (with &) survive escaping.
        $parts = preg_split(
            '#(https?://[^\s<]+)#i',
            $send->body,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $html = '';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $trackUrl = route('track.click', [
                    'token' => $send->tracking_token,
                    'url' => $part,
                ]);
                $html .= '<a href="' . e($trackUrl) . '">' . e($part) . '</a>';
            } else {
                $html .= nl2br(e($part));
            }
        }

        $pixel = '<img src="' . e($openUrl) . '" width="1" height="1" style="display:none" alt="">';

        return '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#181a1c;">'
            . $html . $pixel . '</div>';
    }
}
