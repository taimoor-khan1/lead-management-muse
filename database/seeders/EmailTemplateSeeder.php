<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Cold Outreach',
                'subject' => 'A quick idea for {{business_name}}',
                'body' => "Hi {{contact_person}},\n\nI came across {{business_name}} in {{city}} and noticed your online presence in the {{industry}} space could be stronger.\n\nWe help businesses like yours get more customers with a professional {{suggested_service}}. It takes weeks, not months, and it's built to bring you measurable results.\n\nWould you be open to a quick 15-minute call this week?\n\nBest regards",
            ],
            [
                'name' => 'Follow-up',
                'subject' => 'Following up — {{business_name}}',
                'body' => "Hi {{contact_person}},\n\nJust following up on my earlier note about {{business_name}}. Many {{industry}} businesses in {{city}} are winning new customers online — I'd love to help you do the same with a {{suggested_service}}.\n\nShall we schedule a brief call?\n\nBest regards",
            ],
            [
                'name' => 'Proposal Sent',
                'subject' => 'Your proposal for {{suggested_service}} is ready',
                'body' => "Hi {{contact_person}},\n\nAs discussed, I've prepared a proposal for {{business_name}} covering the {{suggested_service}}.\n\nYou can review and accept it online at your convenience. Happy to walk you through any part of it.\n\nBest regards",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(['name' => $template['name']], $template);
        }
    }
}
