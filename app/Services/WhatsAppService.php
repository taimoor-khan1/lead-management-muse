<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected const API_VERSION = 'v18.0';

    public function isStub(): bool
    {
        return empty(config('services.whatsapp.token'))
            || empty(config('services.whatsapp.phone_id'));
    }

    /**
     * Send a template message via the WhatsApp Cloud API.
     * Returns ['stub' => true, 'id' => ...] when credentials are missing.
     * Throws on API error — callers should catch.
     */
    public function sendTemplateMessage(string $phone, string $templateName, string $language = 'en', array $params = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if (! empty($params)) {
            $payload['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(
                        fn ($p) => ['type' => 'text', 'text' => (string) $p],
                        array_values($params)
                    ),
                ],
            ];
        }

        return $this->post($phone, $payload, 'template', $templateName);
    }

    /**
     * Send a free-form text message via the WhatsApp Cloud API.
     * Returns ['stub' => true, 'id' => ...] when credentials are missing.
     * Throws on API error — callers should catch.
     */
    public function sendTextMessage(string $phone, string $text): array
    {
        return $this->post($phone, [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $text],
        ], 'text');
    }

    protected function post(string $phone, array $payload, string $kind, ?string $label = null): array
    {
        if ($this->isStub()) {
            Log::info('[WhatsApp STUB] Skipped real send — WHATSAPP_TOKEN/WHATSAPP_PHONE_ID not configured.', [
                'to' => $phone,
                'kind' => $kind,
                'label' => $label,
            ]);

            return ['stub' => true, 'id' => 'stub_'.uniqid()];
        }

        $response = Http::withToken(config('services.whatsapp.token'))
            ->timeout(20)
            ->post(
                'https://graph.facebook.com/'.self::API_VERSION.'/'.config('services.whatsapp.phone_id').'/messages',
                $payload
            );

        if (! $response->successful()) {
            throw new \RuntimeException(
                'WhatsApp API error (HTTP '.$response->status().'): '.$response->body()
            );
        }

        $messageId = $response->json('messages.0.id');

        if (! $messageId) {
            throw new \RuntimeException('WhatsApp API returned an unexpected response: '.$response->body());
        }

        return ['id' => $messageId];
    }
}
