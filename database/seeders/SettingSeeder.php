<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'app_name' => 'LeadFlow',
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
            'agency_name' => '',
            'agency_email' => 'admin@leadflow.pk',
            'agency_phone' => '',
            'mail_from_name' => 'LeadFlow',
            'whatsapp_enabled' => '0',
            'google_sheets_enabled' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
