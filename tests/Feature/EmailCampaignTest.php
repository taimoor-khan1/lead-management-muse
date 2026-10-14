<?php

namespace Tests\Feature;

use App\Jobs\SendEmailJob;
use App\Models\Campaign;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailCampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        // No Vite build in this environment; fake Vite so Inertia page
        // renders don't fail on a missing manifest.
        $this->withoutVite();

        $this->user = User::factory()->create();
        $this->lead = Lead::factory()->create([
            'business_name' => 'Lahore Fitness Club',
            'contact_person' => 'Ali Raza',
            'email' => 'ali@example.com',
            'city' => 'Lahore',
            'industry' => 'Fitness',
            'suggested_service' => 'Website Revamp',
        ]);

        $this->actingAs($this->user);
    }

    public function test_email_template_crud(): void
    {
        // create
        $this->post(route('email-templates.store'), [
            'name' => 'Intro',
            'subject' => 'Hi {{business_name}}',
            'body' => 'Hello {{contact_person}}, quick idea for your {{industry}} business.',
        ])->assertRedirect(route('email-templates.index'));

        $template = EmailTemplate::where('name', 'Intro')->first();
        $this->assertNotNull($template);

        // index + create + edit pages render
        $this->get(route('email-templates.index'))->assertOk();
        $this->get(route('email-templates.create'))->assertOk();
        $this->get(route('email-templates.edit', $template))->assertOk();

        // update
        $this->put(route('email-templates.update', $template), [
            'name' => 'Intro v2',
            'subject' => 'Hi {{business_name}}',
            'body' => 'Updated body for {{city}}.',
        ])->assertRedirect(route('email-templates.index'));

        $this->assertSame('Intro v2', $template->fresh()->name);

        // destroy
        $this->delete(route('email-templates.destroy', $template))
            ->assertRedirect(route('email-templates.index'));

        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
    }

    public function test_compose_send_dispatches_job_and_replaces_variables(): void
    {
        Queue::fake();

        $template = EmailTemplate::create([
            'name' => 'Intro',
            'subject' => 'Idea for {{business_name}}',
            'body' => 'Hi {{contact_person}} in {{city}}',
        ]);

        $this->get(route('emails.compose', ['lead_id' => $this->lead->id, 'template_id' => $template->id]))
            ->assertOk();

        $this->post(route('emails.send'), [
            'lead_id' => $this->lead->id,
            'email_template_id' => $template->id,
            'subject' => 'Hi {{business_name}}',
            'body' => 'Dear {{contact_person}} of {{industry}}, {{suggested_service}} for you.',
        ])->assertRedirect(route('emails.index'));

        $this->assertDatabaseHas('email_sends', [
            'lead_id' => $this->lead->id,
            'email_template_id' => $template->id,
            'subject' => 'Hi Lahore Fitness Club',
            'body' => 'Dear Ali Raza of Fitness, Website Revamp for you.',
            'status' => 'queued',
        ]);

        $this->assertNotNull(EmailSend::first()->tracking_token);

        Queue::assertPushed(SendEmailJob::class);
    }

    public function test_send_job_processes_with_sync_queue(): void
    {
        // phpunit.xml already sets QUEUE_CONNECTION=sync; make it explicit.
        config(['queue.default' => 'sync']);

        $this->post(route('emails.send'), [
            'lead_id' => $this->lead->id,
            'subject' => 'Hello {{business_name}}',
            'body' => 'Visit https://example.com for details.',
        ])->assertRedirect(route('emails.index'));

        $send = EmailSend::latest()->first();

        $this->assertSame('sent', $send->status);
        $this->assertNotNull($send->sent_at);
        $this->assertNotNull($this->lead->fresh()->last_contacted_at);
    }

    public function test_tracking_open_increments_opens(): void
    {
        $send = EmailSend::create([
            'lead_id' => $this->lead->id,
            'subject' => 'Hi',
            'body' => 'Body',
            'status' => 'sent',
            'tracking_token' => 'open-token-123',
        ]);

        $response = $this->get(route('track.open', ['token' => 'open-token-123']));

        $response->assertOk();
        $this->assertSame('image/gif', $response->headers->get('Content-Type'));

        $send->refresh();
        $this->assertSame(1, $send->opens);
        $this->assertNotNull($send->opened_at);
        $this->assertSame('opened', $send->status);

        // second open increments count but keeps first opened_at
        $this->get(route('track.open', ['token' => 'open-token-123']));
        $this->assertSame(2, $send->refresh()->opens);
    }

    public function test_tracking_click_increments_clicks_and_redirects(): void
    {
        EmailSend::create([
            'lead_id' => $this->lead->id,
            'subject' => 'Hi',
            'body' => 'Body',
            'status' => 'sent',
            'tracking_token' => 'click-token-123',
        ]);

        $this->get(route('track.click', ['token' => 'click-token-123', 'url' => 'https://example.com/page']))
            ->assertRedirect('https://example.com/page');

        $send = EmailSend::where('tracking_token', 'click-token-123')->first();
        $this->assertSame(1, $send->clicks);
        $this->assertNotNull($send->clicked_at);
        $this->assertSame('clicked', $send->status);
    }

    public function test_tracking_click_rejects_non_http_url(): void
    {
        EmailSend::create([
            'lead_id' => $this->lead->id,
            'subject' => 'Hi',
            'body' => 'Body',
            'status' => 'sent',
            'tracking_token' => 'click-token-456',
        ]);

        $this->get(route('track.click', ['token' => 'click-token-456', 'url' => 'javascript:alert(1)']))
            ->assertRedirect('/');
    }

    public function test_campaign_create_and_launch(): void
    {
        Queue::fake();

        $template = EmailTemplate::create([
            'name' => 'Intro',
            'subject' => 'Idea for {{business_name}}',
            'body' => 'Hi {{contact_person}}, quick idea.',
        ]);

        $this->post(route('campaigns.store'), [
            'name' => 'October Outreach',
            'channel' => 'email',
            'email_template_id' => $template->id,
            'lead_ids' => [$this->lead->id],
        ])->assertRedirect();

        $campaign = Campaign::where('name', 'October Outreach')->first();
        $this->assertNotNull($campaign);
        $this->assertSame('draft', $campaign->status);

        $this->assertDatabaseHas('campaign_leads', [
            'campaign_id' => $campaign->id,
            'lead_id' => $this->lead->id,
            'status' => 'pending',
        ]);

        $this->get(route('campaigns.index'))->assertOk();
        $this->get(route('campaigns.create'))->assertOk();
        $this->get(route('campaigns.show', $campaign))->assertOk();

        $this->post(route('campaigns.launch', $campaign))->assertRedirect();

        Queue::assertPushed(SendEmailJob::class);
    }

    public function test_campaign_launch_sync_sends_and_updates_statuses(): void
    {
        config(['queue.default' => 'sync']);

        $template = EmailTemplate::create([
            'name' => 'Intro',
            'subject' => 'Idea for {{business_name}}',
            'body' => 'Hi {{contact_person}}.',
        ]);

        $campaign = Campaign::create([
            'name' => 'Sync Launch',
            'channel' => 'email',
            'email_template_id' => $template->id,
            'status' => 'draft',
        ]);
        $campaign->leads()->attach($this->lead->id, ['status' => 'pending']);

        $this->post(route('campaigns.launch', $campaign))->assertRedirect();

        $this->assertDatabaseHas('email_sends', [
            'lead_id' => $this->lead->id,
            'subject' => 'Idea for Lahore Fitness Club',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('campaign_leads', [
            'campaign_id' => $campaign->id,
            'lead_id' => $this->lead->id,
            'status' => 'sent',
        ]);

        $this->assertSame('completed', $campaign->fresh()->status);
    }

    public function test_campaign_launch_rejects_non_draft(): void
    {
        $campaign = Campaign::create([
            'name' => 'Already Launched',
            'channel' => 'email',
            'status' => 'completed',
        ]);

        $this->post(route('campaigns.launch', $campaign))
            ->assertRedirect()
            ->assertSessionHasErrors('campaign');
    }
}
