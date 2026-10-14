<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // No frontend build is run for these tests; skip Vite manifest lookups.
        $this->withoutVite();

        // Wire the module's routes the same way the coordinator will in web.php.
        // require (not require_once): each test rebuilds the app, so the file
        // must be re-included per test; it declares no functions/classes.
        if (! Route::has('pipeline.index')) {
            require base_path('routes/dashboard.php');
        }

        // The Breeze closure on GET /dashboard is registered first in web.php, so
        // test the controller through a test-only URL alias for the KPI assertions.
        Route::middleware(['auth'])->get('/test-dashboard-ctrl', [DashboardController::class, 'index']);
    }

    public function test_dashboard_route_name_points_to_dashboard_controller(): void
    {
        $this->assertTrue(Route::has('dashboard'));
        $this->assertTrue(Route::has('pipeline.index'));
        $this->assertTrue(Route::has('pipeline.move'));

        // routes/dashboard.php must register the dashboard route on DashboardController.
        // (The Breeze closure in web.php currently holds the 'dashboard' name lookup;
        // the coordinator removes it at integration so this registration takes over.)
        $mine = collect(Route::getRoutes()->getRoutes())->first(
            fn ($r) => $r->getName() === 'dashboard'
                && $r->getActionName() === DashboardController::class.'@index'
        );
        $this->assertNotNull($mine, 'DashboardController dashboard route is registered');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/test-dashboard-ctrl')->assertRedirect('/login');
    }

    public function test_dashboard_loads_with_kpis(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Lead::factory()->count(3)->create(['stage' => 'new']);
        Lead::factory()->count(2)->create(['stage' => 'contacted']);
        $repliedLead = Lead::factory()->create(['stage' => 'replied']);
        $wonLead = Lead::factory()->create(['stage' => 'won']);
        Lead::factory()->create(['stage' => 'lost']);

        Proposal::create([
            'lead_id' => $wonLead->id,
            'token' => 'tok-accepted-1',
            'title' => 'Website build',
            'status' => 'accepted',
            'total' => 250000,
        ]);
        Proposal::create([
            'lead_id' => $wonLead->id,
            'token' => 'tok-draft-1',
            'title' => 'Draft proposal',
            'status' => 'draft',
            'total' => 50000,
        ]);

        Task::create([
            'lead_id' => $repliedLead->id,
            'user_id' => $user->id,
            'title' => 'Follow up call',
            'due_date' => now()->addDay()->toDateString(),
            'done' => false,
        ]);

        EmailSend::create([
            'lead_id' => $repliedLead->id,
            'subject' => 'Intro email',
            'body' => 'Hello',
            'status' => 'sent',
            'tracking_token' => 'tok-send-1',
            'sent_at' => now(),
        ]);

        $response = $this->get('/test-dashboard-ctrl');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('kpis.total_leads', 8)
            ->where('kpis.contacted', 3)   // contacted + replied
            ->where('kpis.replied', 2)      // replied + won
            ->where('kpis.won', 1)
            ->where('kpis.revenue_pkr', 250000)
            ->where('pipeline_summary.new', 3)
            ->where('pipeline_summary.contacted', 2)
            ->where('pipeline_summary.replied', 1)
            ->where('pipeline_summary.won', 1)
            ->where('pipeline_summary.lost', 1)
            ->has('recent_leads', 8)
            ->has('upcoming_tasks', 1)
            ->has('recent_sends', 1)
        );
    }

    public function test_pipeline_index_groups_leads_by_stage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Lead::factory()->count(2)->create(['stage' => 'new']);
        Lead::factory()->create(['stage' => 'contacted', 'business_name' => 'Acme Traders']);

        $response = $this->get('/pipeline');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Pipeline')
            ->has('columns', 6)
            ->where('columns.0.key', 'new')
            ->has('columns.0.leads', 2)
            ->where('columns.1.key', 'contacted')
            ->where('columns.1.leads.0.business_name', 'Acme Traders')
        );
    }

    public function test_pipeline_move_changes_stage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lead = Lead::factory()->create(['stage' => 'new', 'last_contacted_at' => null]);

        $response = $this->postJson('/pipeline/move', [
            'lead_id' => $lead->id,
            'stage' => 'contacted',
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $lead->refresh();
        $this->assertEquals('contacted', $lead->stage);
        $this->assertNotNull($lead->last_contacted_at);
    }

    public function test_pipeline_move_rejects_invalid_stage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lead = Lead::factory()->create(['stage' => 'new']);

        $response = $this->postJson('/pipeline/move', [
            'lead_id' => $lead->id,
            'stage' => 'bogus',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('stage');
        $this->assertEquals('new', $lead->refresh()->stage);
    }

    public function test_pipeline_move_requires_auth(): void
    {
        $lead = Lead::factory()->create(['stage' => 'new']);

        $this->postJson('/pipeline/move', [
            'lead_id' => $lead->id,
            'stage' => 'contacted',
        ])->assertUnauthorized();

        $this->assertEquals('new', $lead->refresh()->stage);
    }
}
