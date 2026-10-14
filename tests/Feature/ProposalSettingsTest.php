<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProposalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // routes/proposals.php is wired into web.php by the coordinator.
        // Load it directly here so this test runs standalone until then.
        // RouteServiceProvider::refreshNameLookups() normally runs after boot;
        // since we register routes late we must refresh the name table manually.
        if (! Route::has('proposals.index')) {
            require base_path('routes/proposals.php');
            Route::getRoutes()->refreshNameLookups();
        }
    }

    protected function user(): User
    {
        return User::factory()->create();
    }

    protected function lead(): Lead
    {
        return Lead::create([
            'business_name' => 'Lahore Bakers',
            'contact_person' => 'Ahmed Khan',
            'city' => 'Lahore',
            'industry' => 'Food',
        ]);
    }

    public function test_service_crud(): void
    {
        $this->actingAs($this->user());

        $this->post(route('services.store'), [
            'name' => 'Website Design',
            'description' => '5-page business website',
            'price' => 75000,
            'active' => true,
        ])->assertRedirect(route('settings.index'));

        $service = Service::where('name', 'Website Design')->first();
        $this->assertNotNull($service);
        $this->assertEquals(75000, $service->price);
        $this->assertEquals('PKR', $service->currency);

        $this->put(route('services.update', $service), [
            'name' => 'Website Design',
            'description' => 'Updated description',
            'price' => 80000,
            'active' => true,
        ])->assertRedirect(route('settings.index'));

        $this->assertEquals(80000, $service->fresh()->price);
        $this->assertEquals('Updated description', $service->fresh()->description);

        $this->delete(route('services.destroy', $service))->assertRedirect(route('settings.index'));
        $this->assertNull(Service::find($service->id));
    }

    public function test_proposal_create_computes_totals(): void
    {
        $this->actingAs($this->user());
        $lead = $this->lead();
        $service = Service::create(['name' => 'SEO', 'price' => 30000, 'currency' => 'PKR', 'active' => true]);

        $response = $this->post(route('proposals.store'), [
            'lead_id' => $lead->id,
            'title' => 'Q4 Marketing Proposal',
            'valid_until' => '2026-12-31',
            'notes' => 'Payable in two milestones.',
            'discount' => 10000,
            'items' => [
                ['service_id' => $service->id, 'name' => 'SEO', 'qty' => 2, 'price' => 30000],
                ['service_id' => null, 'name' => 'Logo tweak', 'qty' => 1, 'price' => 15000],
            ],
        ]);

        $proposal = Proposal::where('title', 'Q4 Marketing Proposal')->first();
        $this->assertNotNull($proposal);
        $response->assertRedirect(route('proposals.show', $proposal));

        // subtotal = 2*30000 + 1*15000 = 75000, discount 10000, total 65000
        $this->assertEquals(75000, $proposal->subtotal);
        $this->assertEquals(10000, $proposal->discount);
        $this->assertEquals(65000, $proposal->total);
        $this->assertEquals('draft', $proposal->status);
        $this->assertEquals(32, strlen($proposal->token));
        $this->assertCount(2, $proposal->items);
    }

    public function test_proposal_discount_capped_at_subtotal(): void
    {
        $this->actingAs($this->user());

        $this->post(route('proposals.store'), [
            'lead_id' => $this->lead()->id,
            'title' => 'Big discount',
            'discount' => 999999,
            'items' => [
                ['name' => 'Small job', 'qty' => 1, 'price' => 5000],
            ],
        ]);

        $proposal = Proposal::where('title', 'Big discount')->first();
        $this->assertEquals(5000, $proposal->subtotal);
        $this->assertEquals(5000, $proposal->discount);
        $this->assertEquals(0, $proposal->total);
    }

    public function test_proposals_send_sets_sent(): void
    {
        $this->actingAs($this->user());
        $proposal = Proposal::create([
            'lead_id' => $this->lead()->id,
            'token' => 'sendtesttoken1234567890abcdef',
            'title' => 'To send',
            'status' => 'draft',
            'subtotal' => 1000,
            'total' => 1000,
        ]);

        $this->post(route('proposals.send', $proposal))->assertRedirect(route('proposals.show', $proposal));
        $this->assertEquals('sent', $proposal->fresh()->status);
    }

    public function test_public_proposal_page_loads_by_token_without_login(): void
    {
        $proposal = Proposal::create([
            'lead_id' => $this->lead()->id,
            'token' => 'publictoken1234567890abcdef12',
            'title' => 'Public View Proposal',
            'status' => 'sent',
            'subtotal' => 50000,
            'discount' => 5000,
            'total' => 45000,
            'notes' => 'Some notes here.',
        ]);
        $proposal->items()->create(['name' => 'Website', 'qty' => 1, 'price' => 50000]);

        $response = $this->get('/p/'.$proposal->token);
        $response->assertOk();
        $response->assertSee('Public View Proposal');
        $response->assertSee('Lahore Bakers');
        $response->assertSee('PKR 45,000');
    }

    public function test_public_proposal_page_404_for_bad_token(): void
    {
        $this->get('/p/does-not-exist-token')->assertNotFound();
    }

    public function test_public_accept_sets_status_and_lead_won(): void
    {
        $lead = $this->lead();
        $proposal = Proposal::create([
            'lead_id' => $lead->id,
            'token' => 'accepttoken1234567890abcdef12',
            'title' => 'Accept me',
            'status' => 'sent',
            'subtotal' => 20000,
            'total' => 20000,
        ]);

        $this->post('/p/'.$proposal->token.'/accept')->assertRedirect();

        $this->assertEquals('accepted', $proposal->fresh()->status);
        $this->assertEquals('won', $lead->fresh()->stage);
    }

    public function test_public_decline_sets_declined_and_stores_reason(): void
    {
        $proposal = Proposal::create([
            'lead_id' => $this->lead()->id,
            'token' => 'declinetoken1234567890abcdef1',
            'title' => 'Decline me',
            'status' => 'sent',
            'subtotal' => 20000,
            'total' => 20000,
            'notes' => 'Original note.',
        ]);

        $this->post('/p/'.$proposal->token.'/decline', ['reason' => 'Too expensive right now'])
            ->assertRedirect();

        $fresh = $proposal->fresh();
        $this->assertEquals('declined', $fresh->status);
        $this->assertStringContainsString('Original note.', $fresh->notes);
        $this->assertStringContainsString('Too expensive right now', $fresh->notes);
    }

    public function test_settings_update_stores_keys(): void
    {
        $this->actingAs($this->user());

        $this->post(route('settings.update'), [
            'company_name' => 'PixelsSoft',
            'company_email' => 'hello@pixelssoft.com',
            'company_phone' => '0300-1234567',
            'WHATSAPP_TOKEN' => 'secret-wa-token',
            'MAIL_HOST' => 'smtp.example.com',
        ])->assertRedirect(route('settings.index'));

        $this->assertEquals('PixelsSoft', Setting::where('key', 'company_name')->first()->value);
        $this->assertEquals('hello@pixelssoft.com', Setting::where('key', 'company_email')->first()->value);
        $this->assertEquals('secret-wa-token', Setting::where('key', 'WHATSAPP_TOKEN')->first()->value);
        $this->assertEquals('smtp.example.com', Setting::where('key', 'MAIL_HOST')->first()->value);
    }

    public function test_settings_update_does_not_wipe_secrets_with_blank_input(): void
    {
        $this->actingAs($this->user());
        Setting::create(['key' => 'WHATSAPP_TOKEN', 'value' => 'keep-me']);

        $this->post(route('settings.update'), [
            'company_name' => 'PixelsSoft',
            'WHATSAPP_TOKEN' => '',
        ]);

        $this->assertEquals('keep-me', Setting::where('key', 'WHATSAPP_TOKEN')->first()->value);
    }

    public function test_settings_page_shows_masked_secrets(): void
    {
        $this->actingAs($this->user());
        Setting::create(['key' => 'WHATSAPP_TOKEN', 'value' => 'abcdefgh12345678']);

        $response = $this->get(route('settings.index'));
        $response->assertOk();
        // Masked preview shown, raw secret never rendered.
        $response->assertSee('••••5678', false);
        $response->assertDontSee('abcdefgh12345678');
    }
}
