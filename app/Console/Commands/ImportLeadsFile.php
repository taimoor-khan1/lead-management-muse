<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetService;
use Illuminate\Console\Command;

/**
 * One-shot import of verified daily leads from the scraping cron's JSON file.
 *
 * File format (same as /tmp/leads-YYYY-MM-DD.json produced by the daily cron):
 *   {
 *     "Schools": [
 *       ["business_name", "contact_person", "phone", "email", "address",
 *        "latitude", "longitude", "website", "city", "notes", "suggested_service"],
 *       ...
 *     ],
 *     "Hospitals": [ ... ]
 *   }
 *
 * Each tab name becomes the lead's `industry`. Missing fields in the file use
 * the em dash "—" (converted to null). Duplicate business names are skipped,
 * matching the behaviour of the Google Sheet sync.
 *
 * Example:
 *   php artisan app:import-leads-file /tmp/leads-2026-10-14.json
 *   php artisan app:import-leads-file /tmp/leads-2026-10-14.json --dry-run
 */
class ImportLeadsFile extends Command
{
    protected $signature = 'app:import-leads-file {path : Path to the leads JSON file} {--dry-run : Parse and report counts without writing}';

    protected $description = 'Import verified leads from the daily scrape JSON file (one entry per industry tab)';

    /**
     * Positional columns of each row in the cron's JSON format.
     */
    private const COLUMNS = [
        'business name', 'contact person', 'phone', 'email', 'address',
        'latitude', 'longitude', 'website', 'city', 'notes', 'suggested service',
    ];

    public function handle(GoogleSheetService $service): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data) || $data === []) {
            $this->error('File is not a valid JSON object with industry tabs.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($data as $tab => $tabRows) {
            if (! is_array($tabRows)) {
                continue;
            }

            foreach ($tabRows as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $row = ['industry' => (string) $tab];

                foreach (self::COLUMNS as $i => $header) {
                    $value = $line[$i] ?? null;
                    if ($value === '—') {
                        $value = null;
                    }
                    $row[$header] = $value;
                }

                $rows[] = $row;
            }
        }

        $this->info('Tabs: '.count($data).' | Rows parsed: '.count($rows));

        if ($this->option('dry-run')) {
            $this->info('Dry run — nothing written to the database.');

            return self::SUCCESS;
        }

        $result = $service->importRows($rows, 'leads_file');

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
