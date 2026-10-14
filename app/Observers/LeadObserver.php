<?php

namespace App\Observers;

use App\Models\Automation;
use App\Models\Lead;
use App\Services\AutomationRunner;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        $this->fire('lead_created', $lead);
    }

    public function updated(Lead $lead): void
    {
        if ($lead->wasChanged('stage')) {
            $this->fire('stage_changed', $lead);
        }
    }

    protected function fire(string $trigger, Lead $lead): void
    {
        $rules = Automation::where('active', true)
            ->where('trigger', $trigger)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        $runner = app(AutomationRunner::class);

        foreach ($rules as $automation) {
            // Optional filter: only fire when the new stage matches config.stage
            if ($trigger === 'stage_changed') {
                $stage = $automation->config['stage'] ?? null;
                if ($stage && $lead->stage !== $stage) {
                    continue;
                }
            }

            $runner->runAction($automation, $lead);
            $automation->update(['last_run_at' => now()]);
        }
    }
}
