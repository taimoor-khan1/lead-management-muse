<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AutomationRunner
{
    /**
     * Evaluate the no_reply_days trigger for one automation.
     * Returns the number of actions executed.
     */
    public function evaluateNoReply(Automation $automation): int
    {
        $days = (int) ($automation->config['days'] ?? 7);
        $days = $days > 0 ? $days : 7;
        $cutoff = now()->subDays($days);

        $leads = Lead::where('stage', 'contacted')
            ->whereNotNull('last_contacted_at')
            ->where('last_contacted_at', '<=', $cutoff)
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)->from('email_sends')
                    ->whereColumn('email_sends.lead_id', 'leads.id')
                    ->where('email_sends.status', 'replied')
                    ->whereRaw('email_sends.created_at >= leads.last_contacted_at');
            })
            ->get();

        $count = 0;
        foreach ($leads as $lead) {
            if ($this->runAction($automation, $lead)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Execute a single automation action against a lead.
     * Returns true when the action actually happened.
     */
    public function runAction(Automation $automation, Lead $lead): bool
    {
        $config = $automation->config ?? [];

        return match ($automation->action) {
            'create_task' => $this->createTask($lead, $config),
            'send_email' => $this->sendEmail($lead, $config),
            'change_stage' => $this->changeStage($lead, $config),
            default => false,
        };
    }

    protected function createTask(Lead $lead, array $config): bool
    {
        $userId = $lead->owner_id ?? User::query()->value('id');
        if (! $userId) {
            return false;
        }

        Task::create([
            'user_id' => $userId,
            'lead_id' => $lead->id,
            'title' => $config['task_title'] ?: 'Follow up',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        return true;
    }

    protected function sendEmail(Lead $lead, array $config): bool
    {
        $template = ! empty($config['template_id'])
            ? EmailTemplate::find($config['template_id'])
            : null;

        if (! $template || ! $lead->email) {
            return false;
        }

        $send = EmailSend::create([
            'lead_id' => $lead->id,
            'email_template_id' => $template->id,
            'subject' => $this->interpolate($template->subject, $lead),
            'body' => $this->interpolate($template->body, $lead),
            'status' => 'queued',
            'tracking_token' => (string) Str::uuid(),
        ]);

        try {
            Mail::raw($send->body, function ($message) use ($lead, $send) {
                $message->to($lead->email)->subject($send->subject);
            });
            $send->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $send->update(['status' => 'failed', 'error' => $e->getMessage()]);

            return false;
        }

        $lead->update(['last_contacted_at' => now()]);

        return true;
    }

    protected function changeStage(Lead $lead, array $config): bool
    {
        $stage = $config['stage'] ?? null;
        if (! $stage || $lead->stage === $stage) {
            return false;
        }

        $lead->update(['stage' => $stage]);

        return true;
    }

    protected function interpolate(string $text, Lead $lead): string
    {
        return str_replace(
            ['{{business_name}}', '{{contact_person}}', '{{city}}', '{{industry}}', '{{suggested_service}}'],
            [
                $lead->business_name,
                $lead->contact_person ?? '',
                $lead->city ?? '',
                $lead->industry ?? '',
                $lead->suggested_service ?? '',
            ],
            $text
        );
    }
}
