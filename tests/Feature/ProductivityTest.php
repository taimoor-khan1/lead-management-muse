<?php

namespace Tests\Feature;

use App\Models\Automation;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_crud_and_toggle(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create(['business_name' => 'Task Test Biz', 'owner_id' => $user->id]);

        // store
        $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Call owner',
            'lead_id' => $lead->id,
            'due_date' => now()->addDay()->toDateString(),
        ])->assertRedirect();

        $task = Task::where('user_id', $user->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('Call owner', $task->title);
        $this->assertEquals($lead->id, $task->lead_id);
        $this->assertFalse((bool) $task->done);

        // another user cannot toggle someone else's task
        $other = User::factory()->create();
        $this->actingAs($other)->post(route('tasks.toggle', $task->id))->assertForbidden();

        // toggle flips done
        $this->actingAs($user)->post(route('tasks.toggle', $task->id))->assertRedirect();
        $this->assertTrue($task->fresh()->done);
        $this->actingAs($user)->post(route('tasks.toggle', $task->id))->assertRedirect();
        $this->assertFalse($task->fresh()->done);

        // index loads with filters
        $this->actingAs($user)->get(route('tasks.index'))->assertOk()->assertInertia(fn ($p) => $p->has('tasks'));
        $this->actingAs($user)->get(route('tasks.index', ['filter' => 'open']))->assertOk();
        $this->actingAs($user)->get(route('tasks.index', ['filter' => 'overdue']))->assertOk();
        $this->actingAs($user)->get(route('tasks.index', ['filter' => 'done']))->assertOk();

        // destroy
        $this->actingAs($user)->delete(route('tasks.destroy', $task->id))->assertRedirect();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_validation_requires_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tasks.store'), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_note_store_and_destroy(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create(['business_name' => 'Note Test Biz']);

        $this->actingAs($user)->post(route('leads.notes.store', $lead), ['body' => 'Called, asked for a quote.'])
            ->assertRedirect();

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'body' => 'Called, asked for a quote.',
        ]);

        $note = LeadNote::first();
        $this->actingAs($user)->delete(route('leads.notes.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('lead_notes', ['id' => $note->id]);
    }

    public function test_automation_crud(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'No reply follow-up',
            'trigger' => 'no_reply_days',
            'action' => 'create_task',
            'config' => ['days' => 3, 'task_title' => 'Follow up'],
            'active' => true,
        ])->assertRedirect();

        $automation = Automation::first();
        $this->assertNotNull($automation);
        $this->assertEquals(['days' => 3, 'task_title' => 'Follow up'], $automation->config);

        $this->actingAs($user)->get(route('automations.index'))->assertOk();

        $this->actingAs($user)->patch(route('automations.update', $automation), [
            'name' => 'No reply follow-up v2',
            'trigger' => 'no_reply_days',
            'action' => 'create_task',
            'config' => ['days' => 5, 'task_title' => 'Ping again'],
            'active' => false,
        ])->assertRedirect();
        $this->assertEquals('No reply follow-up v2', $automation->fresh()->name);
        $this->assertFalse((bool) $automation->fresh()->active);

        $this->actingAs($user)->delete(route('automations.destroy', $automation))->assertRedirect();
        $this->assertDatabaseMissing('automations', ['id' => $automation->id]);
    }

    public function test_no_reply_automation_creates_task(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create([
            'business_name' => 'Silent Lead',
            'owner_id' => $user->id,
            'stage' => 'contacted',
            'last_contacted_at' => now()->subDays(10),
        ]);

        // a fresh contacted lead must NOT get a task
        Lead::create([
            'business_name' => 'Fresh Lead',
            'owner_id' => $user->id,
            'stage' => 'contacted',
            'last_contacted_at' => now()->subDay(),
        ]);

        Automation::create([
            'name' => 'No reply follow-up',
            'trigger' => 'no_reply_days',
            'action' => 'create_task',
            'config' => ['days' => 3, 'task_title' => 'Follow up with lead'],
            'active' => true,
        ]);

        $this->artisan('app:evaluate-automations')->assertSuccessful();

        $this->assertDatabaseHas('tasks', [
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'title' => 'Follow up with lead',
        ]);
        $this->assertEquals(1, Task::count());
        $this->assertNotNull(Automation::first()->fresh()->last_run_at);
    }

    public function test_no_reply_automation_send_email_skips_gracefully_without_template(): void
    {
        $user = User::factory()->create();
        Lead::create([
            'business_name' => 'Silent Lead 2',
            'owner_id' => $user->id,
            'stage' => 'contacted',
            'email' => 'owner@example.com',
            'last_contacted_at' => now()->subDays(10),
        ]);

        Automation::create([
            'name' => 'Email nudge',
            'trigger' => 'no_reply_days',
            'action' => 'send_email',
            'config' => ['days' => 3, 'template_id' => 999999], // missing template
            'active' => true,
        ]);

        $this->artisan('app:evaluate-automations')->assertSuccessful();
        $this->assertEquals(0, Task::count());
        $this->assertDatabaseMissing('email_sends', []);
    }

    public function test_stage_changed_observer_creates_task(): void
    {
        $user = User::factory()->create();
        $lead = Lead::create(['business_name' => 'Stage Biz', 'owner_id' => $user->id, 'stage' => 'new']);

        Automation::create([
            'name' => 'Reply task',
            'trigger' => 'stage_changed',
            'action' => 'create_task',
            'config' => ['stage' => 'replied', 'task_title' => 'Send proposal'],
            'active' => true,
        ]);

        $lead->update(['stage' => 'contacted']);
        $this->assertEquals(0, Task::count()); // filter stage did not match

        $lead->update(['stage' => 'replied']);
        $this->assertDatabaseHas('tasks', [
            'lead_id' => $lead->id,
            'title' => 'Send proposal',
        ]);
    }

    public function test_lead_created_observer_runs_automation(): void
    {
        $user = User::factory()->create();

        Automation::create([
            'name' => 'Welcome task',
            'trigger' => 'lead_created',
            'action' => 'create_task',
            'config' => ['task_title' => 'Qualify new lead'],
            'active' => true,
        ]);

        $lead = Lead::create(['business_name' => 'Fresh Biz', 'owner_id' => $user->id]);

        $this->assertDatabaseHas('tasks', [
            'lead_id' => $lead->id,
            'title' => 'Qualify new lead',
        ]);
    }

    public function test_reports_page_loads_with_data(): void
    {
        $user = User::factory()->create();
        Lead::create(['business_name' => 'R1', 'stage' => 'new', 'industry' => 'Restaurant', 'city' => 'Lahore']);
        Lead::create(['business_name' => 'R2', 'stage' => 'won', 'industry' => 'Restaurant', 'city' => 'Karachi']);
        Lead::create(['business_name' => 'R3', 'stage' => 'contacted', 'industry' => 'Salon', 'city' => 'Lahore']);
        Task::create(['user_id' => $user->id, 'title' => 'T1', 'due_date' => now()->subDay()->toDateString()]);
        Task::create(['user_id' => $user->id, 'title' => 'T2', 'done' => true]);

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('funnel', 6)
                ->has('industryCounts')
                ->has('campaignStats')
                ->has('proposalStats')
                ->has('tasksStats')
                ->where('funnel.0.count', 1)
                ->where('tasksStats.overdue', 1)
                ->where('tasksStats.done', 1)
            );
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('tasks.index'))->assertRedirect('/login');
        $this->get(route('automations.index'))->assertRedirect('/login');
        $this->get(route('reports.index'))->assertRedirect('/login');
    }
}
