<?php

namespace Tests\Feature;

use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WhatsappInboxTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = User::factory()->create();

        // Load this module's routes the same way the app will (inside the
        // "web" middleware group) until the coordinator requires
        // routes/whatsapp.php in web.php.
        if (! Route::has('whatsapp.index')) {
            Route::middleware('web')->group(base_path('routes/whatsapp.php'));
            app('router')->getRoutes()->refreshNameLookups();
        }
    }

    // --- Webhook: verification handshake ---

    public function test_webhook_verify_succeeds_with_correct_token(): void
    {
        config(['services.whatsapp.verify_token' => 'secret-123']);

        $response = $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=secret-123&hub.challenge=challenge-abc');

        $response->assertStatus(200);
        $this->assertSame('challenge-abc', $response->getContent());
    }

    public function test_webhook_verify_forbidden_with_wrong_token(): void
    {
        config(['services.whatsapp.verify_token' => 'secret-123']);

        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=x')
            ->assertStatus(403);

        // Not configured at all → also forbidden
        config(['services.whatsapp.verify_token' => null]);
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=anything&hub.challenge=x')
            ->assertStatus(403);
    }

    // --- Webhook: incoming messages ---

    public function test_webhook_incoming_message_creates_lead_and_message(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'messages' => [
                                    [
                                        'from' => '923001234567',
                                        'id' => 'wamid.abc123',
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello, I need a website'],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/whatsapp', $payload);
        $response->assertStatus(200);

        $lead = Lead::where('phone', '923001234567')->first();
        $this->assertNotNull($lead);
        $this->assertSame('whatsapp', $lead->source);
        $this->assertNotNull($lead->last_contacted_at);

        $message = WhatsappMessage::where('lead_id', $lead->id)->first();
        $this->assertNotNull($message);
        $this->assertSame('in', $message->direction);
        $this->assertSame('Hello, I need a website', $message->body);
        $this->assertSame('wamid.abc123', $message->wa_message_id);
    }

    public function test_webhook_incoming_message_reuses_existing_lead(): void
    {
        $lead = Lead::create([
            'business_name' => 'Existing Co',
            'phone' => '923007654321',
            'source' => 'manual',
        ]);

        $this->postJson('/webhooks/whatsapp', [
            'entry' => [
                ['changes' => [[
                    'value' => ['messages' => [[
                        'from' => '923007654321',
                        'id' => 'wamid.xyz',
                        'type' => 'text',
                        'text' => ['body' => 'hi again'],
                    ]]],
                ]]],
            ],
        ])->assertStatus(200);

        $this->assertEquals(1, Lead::where('phone', '923007654321')->count());
        $this->assertEquals(1, WhatsappMessage::where('lead_id', $lead->id)->count());
    }

    // --- WhatsAppService stub mode ---

    public function test_whatsapp_service_stub_returns_stub_id_without_creds(): void
    {
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_id' => null]);

        $service = new WhatsAppService;
        $this->assertTrue($service->isStub());

        $text = $service->sendTextMessage('923001234567', 'hello');
        $this->assertTrue($text['stub'] ?? false);
        $this->assertStringStartsWith('stub_', $text['id']);

        $template = $service->sendTemplateMessage('923001234567', 'hello_world');
        $this->assertTrue($template['stub'] ?? false);
        $this->assertStringStartsWith('stub_', $template['id']);
    }

    // --- WhatsApp UI ---

    public function test_whatsapp_index_loads(): void
    {
        $lead = Lead::create(['business_name' => 'Chat Lead', 'phone' => '923001111111']);
        WhatsappMessage::create([
            'lead_id' => $lead->id,
            'phone' => '923001111111',
            'direction' => 'in',
            'body' => 'salam',
            'status' => 'received',
        ]);

        $this->actingAs($this->user)
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WhatsApp/Index')
                ->has('threads', 1)
            );
    }

    public function test_whatsapp_thread_loads(): void
    {
        $lead = Lead::create(['business_name' => 'Thread Lead', 'phone' => '923002222222']);
        WhatsappTemplate::create(['name' => 'hello_world', 'body' => 'Hello!']);

        $this->actingAs($this->user)
            ->get(route('whatsapp.thread', $lead))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('WhatsApp/Thread')
                ->has('messages')
                ->has('templates', 1)
            );
    }

    public function test_whatsapp_send_stores_outgoing_message(): void
    {
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_id' => null]);
        $lead = Lead::create(['business_name' => 'Send Lead', 'phone' => '923003333333']);

        $this->actingAs($this->user)
            ->post(route('whatsapp.send'), [
                'lead_id' => $lead->id,
                'body' => 'thanks for reaching out',
            ])
            ->assertRedirect();

        $message = WhatsappMessage::where('lead_id', $lead->id)->first();
        $this->assertNotNull($message);
        $this->assertSame('out', $message->direction);
        $this->assertSame('thanks for reaching out', $message->body);
        $this->assertSame('stubbed', $message->status);
        $this->assertNotNull($lead->fresh()->last_contacted_at);
    }

    // --- Unified inbox ---

    public function test_inbox_page_loads(): void
    {
        $lead = Lead::create(['business_name' => 'Inbox Lead', 'phone' => '923004444444']);
        WhatsappMessage::create([
            'lead_id' => $lead->id,
            'phone' => '923004444444',
            'direction' => 'in',
            'body' => 'interested',
        ]);
        EmailSend::create([
            'lead_id' => $lead->id,
            'subject' => 'Intro',
            'body' => 'body text',
            'status' => 'sent',
            'tracking_token' => 'tok-'.uniqid(),
        ]);

        $this->actingAs($this->user)
            ->get(route('inbox.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inbox/Inbox')
                ->has('whatsapps', 1)
                ->has('emails', 1)
                ->where('counts.whatsapp', 1)
                ->where('counts.emails', 1)
            );
    }

    public function test_mark_email_as_replied(): void
    {
        $lead = Lead::create(['business_name' => 'Replied Lead']);
        $send = EmailSend::create([
            'lead_id' => $lead->id,
            'subject' => 'Intro',
            'body' => 'body text',
            'status' => 'sent',
            'tracking_token' => 'tok-'.uniqid(),
        ]);

        $this->actingAs($this->user)
            ->post(route('emails.mark-replied', $send))
            ->assertRedirect();

        $this->assertSame('replied', $send->fresh()->status);
    }
}
