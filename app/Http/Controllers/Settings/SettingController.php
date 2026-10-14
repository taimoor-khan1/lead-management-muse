<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public const SECRET_KEYS = [
        'GOOGLE_SHEETS_ID',
        'GOOGLE_CREDENTIALS_JSON',
        'WHATSAPP_TOKEN',
        'WHATSAPP_PHONE_ID',
        'WHATSAPP_VERIFY_TOKEN',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
    ];

    public const PROFILE_KEYS = [
        'company_name',
        'company_email',
        'company_phone',
    ];

    public function index()
    {
        $stored = Setting::pluck('value', 'key')->all();

        $configured = [];
        $masked = [];
        foreach (self::SECRET_KEYS as $key) {
            $value = $stored[$key] ?? env($key);
            $configured[$key] = ! empty($value);
            $masked[$key] = $this->mask($key, $value);
        }

        return Inertia::render('Settings', [
            'profile' => [
                'company_name' => $stored['company_name'] ?? '',
                'company_email' => $stored['company_email'] ?? '',
                'company_phone' => $stored['company_phone'] ?? '',
            ],
            'services' => Service::orderBy('name')->get(),
            'integration_keys' => self::SECRET_KEYS,
            'configured' => $configured,
            'masked' => $masked,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'company_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        // Business profile fields: only touch keys that were submitted,
        // so the integrations form and profile form can post independently.
        foreach (self::PROFILE_KEYS as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }

        // Secrets: only overwrite when a non-empty value is submitted,
        // so leaving a masked field blank never wipes the stored secret.
        foreach (self::SECRET_KEYS as $key) {
            $value = $request->input($key);
            if ($value !== null && $value !== '') {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return redirect()->route('settings.index');
    }

    protected function mask(string $key, $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        if ($key === 'GOOGLE_CREDENTIALS_JSON') {
            return '••••••••';
        }
        $value = (string) $value;
        if (strlen($value) <= 8) {
            return '••••••••';
        }

        return '••••'.substr($value, -4);
    }
}
