<?php

namespace Database\Seeders;

use App\Models\Automation;
use Illuminate\Database\Seeder;

class AutomationSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'No reply follow-up task',
                'trigger' => 'no_reply_days',
                'action' => 'create_task',
                'config' => ['days' => 3, 'task_title' => 'Follow up — no reply received'],
                'active' => true,
            ],
            [
                'name' => 'Welcome task for new lead',
                'trigger' => 'lead_created',
                'action' => 'create_task',
                'config' => ['task_title' => 'Reach out to new lead'],
                'active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            Automation::query()->updateOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
