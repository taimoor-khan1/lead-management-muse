<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Services\AutomationRunner;
use Illuminate\Console\Command;

class EvaluateAutomations extends Command
{
    protected $signature = 'app:evaluate-automations';

    protected $description = 'Evaluate active no_reply_days automations and execute their actions';

    public function handle(AutomationRunner $runner): int
    {
        $automations = Automation::where('active', true)
            ->where('trigger', 'no_reply_days')
            ->get();

        $evaluated = 0;
        $actions = 0;

        foreach ($automations as $automation) {
            $count = $runner->evaluateNoReply($automation);
            $automation->update(['last_run_at' => now()]);

            $this->info("[{$automation->name}] {$count} action(s) executed.");
            $evaluated++;
            $actions += $count;
        }

        $this->info("Done. {$evaluated} automation(s) evaluated, {$actions} action(s) executed.");

        return self::SUCCESS;
    }
}
