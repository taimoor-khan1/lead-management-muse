<?php

namespace Database\Seeders;

use App\Models\WhatsappTemplate;
use Illuminate\Database\Seeder;

class WhatsappTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Intro Message',
                'language' => 'en',
                'body' => "Hello {{contact_person}}, this is from LeadFlow. I saw {{business_name}} in {{city}} and wanted to share how a {{suggested_service}} could bring you more customers. May I share a quick proposal?",
            ],
            [
                'name' => 'Follow-up Nudge',
                'language' => 'en',
                'body' => "Hi {{contact_person}}, just checking in — did you get a chance to look at the {{suggested_service}} proposal for {{business_name}}? Happy to answer any questions.",
            ],
        ];

        foreach ($templates as $template) {
            WhatsappTemplate::query()->updateOrCreate(['name' => $template['name']], $template);
        }
    }
}
