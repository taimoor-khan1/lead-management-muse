<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLeadsFileTest extends TestCase
{
    use RefreshDatabase;

    private function fixturePath(): string
    {
        $data = [
            'Schools' => [
                // new lead, full row
                ['Test Academy', '—', '03001234567', '—', 'DHA Phase 2, Karachi', '24.8607', '67.0011', '—', 'Karachi', 'Private School', 'Website'],
                // new lead, em-dash fields must become null
                ['Test Clinic', '—', '—', '—', '—', '—', '—', '—', 'Lahore', '—', 'Website Revamp'],
                // blank business name → skipped
                ['', '—', '—', '—', '—', '—', '—', '—', '—', '—', '—'],
            ],
            'Hospitals' => [
                // duplicate of an existing lead → skipped
                ['Existing Hospital', '—', '0211111111', '—', 'Clifton, Karachi', '24.86', '67.00', '—', 'Karachi', 'Hospital', 'Hospital ERP'],
            ],
        ];

        $path = tempnam(sys_get_temp_dir(), 'leads').'.json';
        file_put_contents($path, json_encode($data));

        return $path;
    }

    public function test_imports_rows_sets_industry_and_skips_duplicates(): void
    {
        Lead::create(['business_name' => 'Existing Hospital', 'stage' => 'new', 'source' => 'manual']);

        $this->artisan('app:import-leads-file', ['path' => $this->fixturePath()])
            ->assertSuccessful();

        $this->assertSame(3, Lead::count());

        $academy = Lead::where('business_name', 'Test Academy')->firstOrFail();
        $this->assertSame('Schools', $academy->industry);
        $this->assertSame('03001234567', $academy->phone);
        $this->assertSame('Website', $academy->suggested_service);
        $this->assertEquals(24.8607, (float) $academy->latitude);
        $this->assertEquals(67.0011, (float) $academy->longitude);
        $this->assertSame('leads_file', $academy->source);

        $clinic = Lead::where('business_name', 'Test Clinic')->firstOrFail();
        $this->assertNull($clinic->phone);
        $this->assertNull($clinic->address);
        $this->assertNull($clinic->latitude);
    }

    public function test_dry_run_writes_nothing(): void
    {
        Lead::create(['business_name' => 'Existing Hospital', 'stage' => 'new', 'source' => 'manual']);

        $this->artisan('app:import-leads-file', ['path' => $this->fixturePath(), '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(1, Lead::count());
    }

    public function test_missing_file_fails(): void
    {
        $this->artisan('app:import-leads-file', ['path' => '/tmp/does-not-exist-leads.json'])
            ->assertFailed();
    }
}
