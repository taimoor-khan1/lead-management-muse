<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetService;
use Illuminate\Console\Command;

class SyncGoogleSheet extends Command
{
    protected $signature = 'app:sync-sheet';

    protected $description = 'Sync leads from the configured Google Sheet (GOOGLE_SHEETS_ID)';

    public function handle(GoogleSheetService $service): int
    {
        $result = $service->syncLeads();

        $this->info("Imported: {$result['imported']} | Skipped: {$result['skipped']}");

        if (! empty($result['message'])) {
            $this->line($result['message']);
        }

        return self::SUCCESS;
    }
}
