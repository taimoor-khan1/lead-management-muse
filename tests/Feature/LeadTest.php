<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // routes/leads.php is a partial wired by the coordinator; load it here
        // so these tests are self-contained until web.php requires it.
        // refreshNameLookups() is needed because Laravel only rebuilds the
        // name lookup table on app boot, not for routes added afterwards.
        if (! Route::has('leads.index')) {
            require base_path('routes/leads.php');
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }
    }

    protected function user(): User
    {
        return User::factory()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('leads.index'))->assertRedirect(route('login'));
        $this->get(route('leads.create'))->assertRedirect(route('login'));
        $this->post(route('leads.store'), [])->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_leads_index(): void
    {
        $this->actingAs($this->user());

        Lead::factory()->create(['business_name' => 'Al-Noor Bakery']);

        $response = $this->get(route('leads.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Leads/Index')
            ->has('leads.data', 1)
        );
    }

    public function test_user_can_create_lead(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $response = $this->post(route('leads.store'), [
            'business_name' => 'City Dental Clinic',
            'contact_person' => 'Dr. Ahmed',
            'phone' => '0300-1234567',
            'email' => 'info@citydental.pk',
            'city' => 'Lahore',
            'industry' => 'Hospital',
            'latitude' => '31.5204',
            'longitude' => '74.3587',
            'website_status' => 'weak',
            'stage' => 'new',
            'suggested_service' => 'Website',
        ]);

        $lead = Lead::where('business_name', 'City Dental Clinic')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('Lahore', $lead->city);
        $this->assertEquals($user->id, $lead->owner_id);
        $response->assertRedirect(route('leads.show', $lead));
    }

    public function test_business_name_is_required(): void
    {
        $this->actingAs($this->user());

        $this->post(route('leads.store'), ['city' => 'Lahore'])
            ->assertSessionHasErrors('business_name');

        $this->assertEquals(0, Lead::count());
    }

    public function test_user_can_update_lead(): void
    {
        $this->actingAs($this->user());
        $lead = Lead::factory()->create(['business_name' => 'Old Name']);

        $response = $this->put(route('leads.update', $lead), [
            'business_name' => 'New Name',
            'city' => 'Karachi',
            'stage' => 'contacted',
        ]);

        $response->assertRedirect(route('leads.show', $lead));
        $this->assertEquals('New Name', $lead->fresh()->business_name);
        $this->assertEquals('contacted', $lead->fresh()->stage);
    }

    public function test_user_can_delete_lead(): void
    {
        $this->actingAs($this->user());
        $lead = Lead::factory()->create();

        $this->delete(route('leads.destroy', $lead))
            ->assertRedirect(route('leads.index'));

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_search_filters_by_name_phone_and_email(): void
    {
        $this->actingAs($this->user());

        Lead::factory()->create(['business_name' => 'Lahore Fitness Gym', 'phone' => '0300-1111111', 'email' => 'gym@example.com']);
        Lead::factory()->create(['business_name' => 'Karachi Food Point', 'phone' => '0300-2222222', 'email' => 'food@example.com']);

        $this->get(route('leads.index', ['search' => 'Fitness']))
            ->assertInertia(fn ($page) => $page
                ->component('Leads/Index')
                ->has('leads.data', 1)
                ->where('leads.data.0.business_name', 'Lahore Fitness Gym')
            );

        $this->get(route('leads.index', ['search' => '0300-2222222']))
            ->assertInertia(fn ($page) => $page
                ->component('Leads/Index')
                ->has('leads.data', 1)
                ->where('leads.data.0.business_name', 'Karachi Food Point')
            );
    }

    public function test_stage_filter(): void
    {
        $this->actingAs($this->user());

        Lead::factory()->create(['business_name' => 'Stage New Lead', 'stage' => 'new']);
        Lead::factory()->create(['business_name' => 'Stage Won Lead', 'stage' => 'won']);

        $this->get(route('leads.index', ['stage' => 'won']))
            ->assertInertia(fn ($page) => $page
                ->component('Leads/Index')
                ->has('leads.data', 1)
                ->where('leads.data.0.business_name', 'Stage Won Lead')
            );
    }

    public function test_csv_import_creates_leads_and_skips_duplicates(): void
    {
        $this->actingAs($this->user());

        Lead::factory()->create(['business_name' => 'Existing Business']);

        $csv = "Business Name,Contact Person,Phone,Email,Address,City,Industry,Website,Notes,Suggested Service\n"
            . "New Import Co,Ali Raza,0300-3333333,new@import.co,Main Road,Lahore,Restaurant,,Fresh lead,Website\n"
            . "Existing Business,Someone,0300-4444444,dup@example.com,,Karachi,Salon,,,Website Revamp\n"
            . "NEW IMPORT CO,Other,0300-5555555,other@example.com,,Islamabad,Hotel,,,Website\n";

        $file = UploadedFile::fake()->createWithContent('leads.csv', $csv);

        $response = $this->post(route('leads.import'), ['file' => $file]);

        $response->assertRedirect(route('leads.index'));
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('leads', ['business_name' => 'New Import Co', 'city' => 'Lahore', 'source' => 'csv']);
        // exact duplicate + case-insensitive duplicate skipped
        $this->assertEquals(1, Lead::whereRaw('LOWER(business_name) = ?', ['existing business'])->count());
        $this->assertEquals(1, Lead::whereRaw('LOWER(business_name) = ?', ['new import co'])->count());
    }

    public function test_csv_import_requires_a_file(): void
    {
        $this->actingAs($this->user());

        $this->post(route('leads.import'), [])->assertSessionHasErrors('file');
    }

    public function test_sync_sheet_without_credentials_returns_graceful_message(): void
    {
        $this->actingAs($this->user());

        // Ensure no credentials leak in from the environment.
        putenv('GOOGLE_SHEETS_ID');
        putenv('GOOGLE_CREDENTIALS_JSON');
        putenv('GOOGLE_CREDENTIALS_PATH');
        unset($_ENV['GOOGLE_SHEETS_ID'], $_SERVER['GOOGLE_SHEETS_ID']);
        unset($_ENV['GOOGLE_CREDENTIALS_JSON'], $_SERVER['GOOGLE_CREDENTIALS_JSON']);
        unset($_ENV['GOOGLE_CREDENTIALS_PATH'], $_SERVER['GOOGLE_CREDENTIALS_PATH']);

        $response = $this->post(route('leads.sync-sheet'));

        // No exception: graceful redirect with the not-configured message.
        $response->assertRedirect();
        $response->assertSessionHas('message', 'Google Sheets not configured — set GOOGLE_SHEETS_ID and GOOGLE_CREDENTIALS_JSON in .env');
        $this->assertEquals(0, Lead::count());
    }

    public function test_sync_sheet_command_handles_missing_config_gracefully(): void
    {
        putenv('GOOGLE_SHEETS_ID');
        putenv('GOOGLE_CREDENTIALS_JSON');
        putenv('GOOGLE_CREDENTIALS_PATH');
        unset($_ENV['GOOGLE_SHEETS_ID'], $_SERVER['GOOGLE_SHEETS_ID']);
        unset($_ENV['GOOGLE_CREDENTIALS_JSON'], $_SERVER['GOOGLE_CREDENTIALS_JSON']);
        unset($_ENV['GOOGLE_CREDENTIALS_PATH'], $_SERVER['GOOGLE_CREDENTIALS_PATH']);

        $this->artisan('app:sync-sheet')
            ->expectsOutputToContain('Imported: 0')
            ->expectsOutputToContain('not configured')
            ->assertSuccessful();
    }
}
