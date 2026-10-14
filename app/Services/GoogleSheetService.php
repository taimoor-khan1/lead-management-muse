<?php

namespace App\Services;

use App\Models\Lead;
use Google\Client as GoogleClient;
use Google\Service\Sheets;

class GoogleSheetService
{
    public const NOT_CONFIGURED_MESSAGE = 'Google Sheets not configured — set GOOGLE_SHEETS_ID and GOOGLE_CREDENTIALS_JSON in .env';

    /**
     * Pull leads from the first sheet of the configured spreadsheet.
     * Never throws — returns a result array with a human message instead.
     */
    public function syncLeads(): array
    {
        $spreadsheetId = env('GOOGLE_SHEETS_ID');
        $credsJson = env('GOOGLE_CREDENTIALS_JSON');
        $credsPath = env('GOOGLE_CREDENTIALS_PATH');

        if (empty($spreadsheetId) || (empty($credsJson) && empty($credsPath))) {
            return ['imported' => 0, 'skipped' => 0, 'message' => self::NOT_CONFIGURED_MESSAGE];
        }

        try {
            $client = new GoogleClient;

            if (! empty($credsJson)) {
                $auth = json_decode($credsJson, true);
                if (! is_array($auth)) {
                    return ['imported' => 0, 'skipped' => 0, 'message' => 'Google Sheets sync failed: GOOGLE_CREDENTIALS_JSON is not valid JSON.'];
                }
                $client->setAuthConfig($auth);
            } else {
                if (! is_file($credsPath)) {
                    return ['imported' => 0, 'skipped' => 0, 'message' => 'Google Sheets sync failed: credentials file not found at GOOGLE_CREDENTIALS_PATH.'];
                }
                $client->setAuthConfig($credsPath);
            }

            $client->addScope(Sheets::SPREADSHEETS_READONLY);

            $service = new Sheets($client);
            // Range without a sheet name reads the first sheet.
            $response = $service->spreadsheets_values->get($spreadsheetId, 'A1:Z10000');
            $rows = $this->rowsFromValues($response->getValues() ?? []);

            return $this->importRows($rows, 'google_sheet');
        } catch (\Throwable $e) {
            return ['imported' => 0, 'skipped' => 0, 'message' => 'Google Sheets sync failed: '.$e->getMessage()];
        }
    }

    /**
     * Create leads from header-keyed rows (header names normalized to lowercase).
     * Skips rows with a blank business name and business names that already exist
     * (case-insensitive), including duplicates inside the same batch.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function importRows(array $rows, string $source = 'google_sheet'): array
    {
        $existing = Lead::query()->pluck('business_name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->flip()
            ->all();

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $data = $this->mapRow($row, $source);

            if ($data === null) {
                $skipped++;

                continue;
            }

            $key = mb_strtolower(trim($data['business_name']));

            if (isset($existing[$key])) {
                $skipped++;

                continue;
            }

            Lead::create($data);
            $existing[$key] = true;
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'message' => "Imported {$imported} lead(s), skipped {$skipped} duplicate/invalid row(s).",
        ];
    }

    /**
     * Parse an uploaded CSV into header-keyed rows (same shape as a sheet export).
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseCsv(string $path): array
    {
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open the uploaded file.');
        }

        $rows = [];
        $headers = null;

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $data);
                if (isset($headers[0])) {
                    $headers[0] = ltrim($headers[0], "\xEF\xBB\xBF"); // strip BOM
                }

                continue;
            }

            if (count(array_filter($data, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function rowsFromValues(array $values): array
    {
        if (count($values) === 0) {
            return [];
        }

        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($values));
        $rows = [];

        foreach ($values as $line) {
            if (count(array_filter($line, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $line[$i] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function mapRow(array $row, string $source): ?array
    {
        $name = trim((string) ($row['business name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $website = trim((string) ($row['website'] ?? ''));

        return [
            'business_name' => $name,
            'contact_person' => $this->nullIfEmpty($row['contact person'] ?? null),
            'phone' => $this->nullIfEmpty($row['phone'] ?? null),
            'email' => $this->nullIfEmpty($row['email'] ?? null),
            'address' => $this->nullIfEmpty($row['address'] ?? null),
            'city' => $this->nullIfEmpty($row['city'] ?? null),
            'industry' => $this->nullIfEmpty($row['industry'] ?? null),
            'website' => $website !== '' ? $website : null,
            'website_status' => $website !== '' ? 'ok' : 'none',
            'notes' => $this->nullIfEmpty($row['notes'] ?? null),
            'suggested_service' => $this->nullIfEmpty($row['suggested service'] ?? null),
            'stage' => 'new',
            'source' => $source,
        ];
    }

    protected function nullIfEmpty(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' || $value === null ? null : (string) $value;
    }
}
