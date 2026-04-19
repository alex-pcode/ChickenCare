<?php

namespace App\Services;

use App\Enums\BatchAgeAtAcquisition;
use App\Enums\BatchEventType;
use App\Enums\DeathCause;
use App\Enums\ExpenseCategory;
use App\Enums\FeedType;
use App\Models\Customer;
use App\Models\FlockBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportDataService
{
    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<string, string> Map of original export IDs to new DB IDs */
    private array $customerIdMap = [];

    /** @var array<string, int> Map of original batch IDs to new DB IDs */
    private array $batchIdMap = [];

    /**
     * Import data from the original ChickenCare app export (JSON).
     *
     * @param  array<string, mixed>  $data
     * @return array{counts: array<string, int>, errors: string[]}
     */
    public function import(User $user, array $data): array
    {
        $errors = [];
        $this->counts = [];
        $this->customerIdMap = [];
        $this->batchIdMap = [];

        DB::transaction(function () use ($user, $data, &$errors) {
            $this->importFlockProfile($user, $data, $errors);
            $this->importFlockEvents($user, $data, $errors);
            $this->importEggEntries($user, $data, $errors);
            $this->importExpenses($user, $data, $errors);
            $this->importFeedInventory($user, $data, $errors);
            $this->importFlockBatches($user, $data, $errors);
            $this->importDeathRecords($user, $data, $errors);
            $this->importBatchEvents($user, $data, $errors);
            $this->importCustomers($user, $data, $errors);
            $this->importSales($user, $data, $errors);
        });

        return [
            'counts' => $this->counts,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importFlockProfile(User $user, array $data, array &$errors): void
    {
        $profile = $data['flockProfile'] ?? null;
        if (! $profile || ! is_array($profile)) {
            return;
        }

        // Skip if user already has a flock profile
        if ($user->flockProfile()->exists()) {
            $errors[] = 'Flock profile already exists — skipped import.';

            return;
        }

        $user->flockProfile()->create([
            'farm_name' => $profile['farm_name'] ?? 'My Chicken Farm',
            'location' => $profile['location'] ?? null,
            'flock_size' => (int) ($profile['flock_size'] ?? ($profile['hens'] ?? 0) + ($profile['roosters'] ?? 0) + ($profile['chicks'] ?? 0) + ($profile['brooding'] ?? 0)),
            'breed' => $profile['breed'] ?? (is_array($profile['breedTypes'] ?? null) ? implode(', ', $profile['breedTypes']) : null),
            'start_date' => $this->parseDate($profile['flockStartDate'] ?? $profile['start_date'] ?? null),
            'hens' => (int) ($profile['hens'] ?? 0),
            'roosters' => (int) ($profile['roosters'] ?? 0),
            'chicks' => (int) ($profile['chicks'] ?? 0),
            'brooding' => (int) ($profile['brooding'] ?? 0),
            'notes' => $profile['notes'] ?? null,
        ]);

        $this->counts['flock_profile'] = 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importFlockEvents(User $user, array $data, array &$errors): void
    {
        $events = $data['flockEvents'] ?? [];
        if (! is_array($events) || count($events) === 0) {
            return;
        }

        $profile = $user->flockProfile;
        if (! $profile) {
            $errors[] = 'No flock profile found — skipped flock events import.';

            return;
        }

        $validTypes = ['acquisition', 'laying_start', 'broody', 'hatching', 'other'];
        $imported = 0;

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $type = $event['type'] ?? 'other';
            if (! in_array($type, $validTypes, true)) {
                $type = 'other';
            }

            $date = $this->parseDate($event['date'] ?? null);
            if (! $date) {
                continue;
            }

            $profile->flockEvents()->create([
                'date' => $date,
                'type' => $type,
                'description' => $this->truncate($event['description'] ?? '', 500),
                'affected_birds' => isset($event['affectedBirds']) ? (int) $event['affectedBirds'] : (isset($event['affected_birds']) ? (int) $event['affected_birds'] : null),
                'notes' => $event['notes'] ?? null,
            ]);
            $imported++;
        }

        $this->counts['flock_events'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importEggEntries(User $user, array $data, array &$errors): void
    {
        $entries = $data['eggEntries'] ?? [];
        if (! is_array($entries) || count($entries) === 0) {
            return;
        }

        $validSizes = ['small', 'medium', 'large', 'extra-large', 'jumbo'];
        $validColors = ['white', 'brown', 'blue', 'green', 'speckled', 'cream'];
        $imported = 0;

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $date = $this->parseDate($entry['date'] ?? null);
            if (! $date) {
                continue;
            }

            $size = $entry['size'] ?? null;
            if ($size && ! in_array($size, $validSizes, true)) {
                $size = null;
            }

            $color = $entry['color'] ?? null;
            if ($color && ! in_array($color, $validColors, true)) {
                $color = null;
            }

            $user->eggEntries()->create([
                'date' => $date,
                'count' => max(0, (int) ($entry['count'] ?? 0)),
                'size' => $size,
                'color' => $color,
                'notes' => isset($entry['notes']) ? $this->truncate((string) $entry['notes'], 1000) : null,
            ]);
            $imported++;
        }

        $this->counts['egg_entries'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importExpenses(User $user, array $data, array &$errors): void
    {
        $expenses = $data['expenses'] ?? [];
        if (! is_array($expenses) || count($expenses) === 0) {
            return;
        }

        $validCategories = array_map(fn (ExpenseCategory $c) => $c->value, ExpenseCategory::cases());
        $imported = 0;

        foreach ($expenses as $expense) {
            if (! is_array($expense)) {
                continue;
            }

            $date = $this->parseDate($expense['date'] ?? null);
            if (! $date) {
                continue;
            }

            $category = $expense['category'] ?? 'Other';
            if (! in_array($category, $validCategories, true)) {
                $category = 'Other';
            }

            $amount = max(0, (float) ($expense['amount'] ?? 0));

            $user->expenses()->create([
                'date' => $date,
                'category' => $category,
                'description' => $this->truncate($expense['description'] ?? '', 500),
                'amount' => $amount,
            ]);
            $imported++;
        }

        $this->counts['expenses'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importFeedInventory(User $user, array $data, array &$errors): void
    {
        $feeds = $data['feedInventory'] ?? [];
        if (! is_array($feeds) || count($feeds) === 0) {
            return;
        }

        $validFeedTypes = array_map(fn (FeedType $t) => $t->value, FeedType::cases());
        $validUnits = ['kg', 'lbs'];
        $imported = 0;

        foreach ($feeds as $feed) {
            if (! is_array($feed)) {
                continue;
            }

            // Map React camelCase fields to Laravel snake_case
            $feedType = $feed['type'] ?? $feed['feed_type'] ?? 'Both';
            if (! in_array($feedType, $validFeedTypes, true)) {
                $feedType = 'Both';
            }

            $unit = $feed['unit'] ?? 'kg';
            if (! in_array($unit, $validUnits, true)) {
                $unit = 'kg';
            }

            $user->feedInventory()->create([
                'brand' => $this->truncate($feed['brand'] ?? $feed['name'] ?? 'Unknown', 255),
                'feed_type' => $feedType,
                'quantity' => max(0, (float) ($feed['quantity'] ?? 0)),
                'unit' => $unit,
                'opened_date' => $this->parseDate($feed['openedDate'] ?? $feed['opened_date'] ?? $feed['purchase_date'] ?? null),
                'depleted_date' => $this->parseDate($feed['depletedDate'] ?? $feed['depleted_date'] ?? $feed['expiry_date'] ?? null),
                'batch_number' => isset($feed['batchNumber']) ? $this->truncate((string) $feed['batchNumber'], 255) : ($feed['batch_number'] ?? null),
                'total_cost' => isset($feed['total_cost']) ? max(0, (float) $feed['total_cost']) : null,
            ]);
            $imported++;
        }

        $this->counts['feed_inventory'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importFlockBatches(User $user, array $data, array &$errors): void
    {
        $batches = $data['flockBatches'] ?? [];
        if (! is_array($batches) || count($batches) === 0) {
            return;
        }

        $validTypes = ['hens', 'roosters', 'chicks', 'mixed'];
        $validAges = array_map(fn (BatchAgeAtAcquisition $a) => $a->value, BatchAgeAtAcquisition::cases());
        $imported = 0;

        foreach ($batches as $batch) {
            if (! is_array($batch)) {
                continue;
            }

            $type = $batch['type'] ?? 'mixed';
            if (! in_array($type, $validTypes, true)) {
                $type = 'mixed';
            }

            $ageAtAcquisition = $batch['ageAtAcquisition'] ?? $batch['age_at_acquisition'] ?? 'adult';
            if (! in_array($ageAtAcquisition, $validAges, true)) {
                $ageAtAcquisition = 'adult';
            }

            $initialCount = max(1, (int) ($batch['initialCount'] ?? $batch['initial_count'] ?? 1));
            $currentCount = max(0, (int) ($batch['currentCount'] ?? $batch['current_count'] ?? $initialCount));

            $newBatch = $user->flockBatches()->create([
                'batch_name' => $this->truncate($batch['batchName'] ?? $batch['batch_name'] ?? 'Imported Batch', 255),
                'breed' => $this->truncate($batch['breed'] ?? 'Unknown', 255),
                'acquisition_date' => $this->parseDate($batch['acquisitionDate'] ?? $batch['acquisition_date'] ?? null) ?? now()->toDateString(),
                'initial_count' => $initialCount,
                'current_count' => $currentCount,
                'hens_count' => max(0, (int) ($batch['hensCount'] ?? $batch['hens_count'] ?? 0)),
                'roosters_count' => max(0, (int) ($batch['roostersCount'] ?? $batch['roosters_count'] ?? 0)),
                'chicks_count' => max(0, (int) ($batch['chicksCount'] ?? $batch['chicks_count'] ?? 0)),
                'brooding_count' => max(0, (int) ($batch['broodingCount'] ?? $batch['brooding_count'] ?? 0)),
                'type' => $type,
                'age_at_acquisition' => $ageAtAcquisition,
                'expected_laying_start_date' => $this->parseDate($batch['expectedLayingStartDate'] ?? $batch['expected_laying_start_date'] ?? null),
                'actual_laying_start_date' => $this->parseDate($batch['actualLayingStartDate'] ?? $batch['actual_laying_start_date'] ?? null),
                'source' => $this->truncate($batch['source'] ?? 'Import', 255),
                'cost' => max(0, (float) ($batch['cost'] ?? 0)),
                'notes' => $batch['notes'] ?? null,
                'is_active' => (bool) ($batch['isActive'] ?? $batch['is_active'] ?? true),
            ]);

            // Map the original export ID to the new database ID
            $originalId = $batch['id'] ?? null;
            if ($originalId) {
                $this->batchIdMap[(string) $originalId] = $newBatch->id;
            }

            $imported++;
        }

        $this->counts['flock_batches'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importDeathRecords(User $user, array $data, array &$errors): void
    {
        $records = $data['deathRecords'] ?? [];
        if (! is_array($records) || count($records) === 0) {
            return;
        }

        $validCauses = array_map(fn (DeathCause $c) => $c->value, DeathCause::cases());
        $imported = 0;
        $skipped = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $date = $this->parseDate($record['date'] ?? null);
            if (! $date) {
                continue;
            }

            // Resolve the batch ID from the import mapping
            $originalBatchId = (string) ($record['batchId'] ?? $record['batch_id'] ?? '');
            $batchId = $this->batchIdMap[$originalBatchId] ?? null;

            if (! $batchId) {
                // Try to find the first active batch as fallback
                $fallbackBatch = $user->flockBatches()->active()->first();
                if (! $fallbackBatch) {
                    $skipped++;

                    continue;
                }
                $batchId = $fallbackBatch->id;
            }

            $cause = $record['cause'] ?? 'unknown';
            if (! in_array($cause, $validCauses, true)) {
                $cause = 'unknown';
            }

            $count = max(1, (int) ($record['count'] ?? 1));

            $batchModel = FlockBatch::find($batchId);
            $batchModel->deathRecords()->create([
                'user_id' => $user->id,
                'date' => $date,
                'count' => $count,
                'cause' => $cause,
                'description' => $this->truncate($record['description'] ?? '', 500),
                'notes' => $record['notes'] ?? null,
            ]);
            $imported++;
        }

        if ($skipped > 0) {
            $errors[] = "{$skipped} death record(s) skipped — no matching batch found.";
        }

        $this->counts['death_records'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importBatchEvents(User $user, array $data, array &$errors): void
    {
        // batch events may be stored under 'batchEvents' in the export
        $events = $data['batchEvents'] ?? [];
        if (! is_array($events) || count($events) === 0) {
            return;
        }

        $validTypes = array_map(fn (BatchEventType $t) => $t->value, BatchEventType::cases());
        $imported = 0;
        $skipped = 0;

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $date = $this->parseDate($event['date'] ?? null);
            if (! $date) {
                continue;
            }

            $originalBatchId = (string) ($event['batchId'] ?? $event['batch_id'] ?? '');
            $batchId = $this->batchIdMap[$originalBatchId] ?? null;

            if (! $batchId) {
                $skipped++;

                continue;
            }

            $type = $event['type'] ?? 'other';
            if (! in_array($type, $validTypes, true)) {
                $type = 'other';
            }

            $batchModel = FlockBatch::find($batchId);
            $batchModel->batchEvents()->create([
                'user_id' => $user->id,
                'date' => $date,
                'type' => $type,
                'description' => $this->truncate($event['description'] ?? '', 500),
                'affected_count' => isset($event['affectedCount']) ? (int) $event['affectedCount'] : (isset($event['affected_count']) ? (int) $event['affected_count'] : null),
                'notes' => $event['notes'] ?? null,
            ]);
            $imported++;
        }

        if ($skipped > 0) {
            $errors[] = "{$skipped} batch event(s) skipped — no matching batch found.";
        }

        $this->counts['batch_events'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importCustomers(User $user, array $data, array &$errors): void
    {
        $customers = $data['customers'] ?? [];
        if (! is_array($customers) || count($customers) === 0) {
            return;
        }

        $imported = 0;

        foreach ($customers as $customer) {
            if (! is_array($customer)) {
                continue;
            }

            $newCustomer = $user->customers()->create([
                'name' => $this->truncate($customer['name'] ?? 'Unknown', 255),
                'phone' => isset($customer['phone']) ? $this->truncate((string) $customer['phone'], 50) : null,
                'notes' => $customer['notes'] ?? null,
                'is_active' => (bool) ($customer['is_active'] ?? true),
            ]);

            $originalId = $customer['id'] ?? null;
            if ($originalId) {
                $this->customerIdMap[(string) $originalId] = $newCustomer->id;
            }

            $imported++;
        }

        $this->counts['customers'] = $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  string[]  $errors
     */
    private function importSales(User $user, array $data, array &$errors): void
    {
        $sales = $data['sales'] ?? [];
        if (! is_array($sales) || count($sales) === 0) {
            return;
        }

        $imported = 0;
        $unmatchedCustomers = 0;

        foreach ($sales as $sale) {
            if (! is_array($sale)) {
                continue;
            }

            $saleDate = $this->parseDate($sale['sale_date'] ?? $sale['saleDate'] ?? null);
            if (! $saleDate) {
                continue;
            }

            // Resolve customer ID from mapping
            $originalCustomerId = (string) ($sale['customer_id'] ?? $sale['customerId'] ?? '');
            $customerId = $this->customerIdMap[$originalCustomerId] ?? null;

            if (! $customerId && $originalCustomerId) {
                $unmatchedCustomers++;
            }

            $user->sales()->create([
                'customer_id' => $customerId,
                'sale_date' => $saleDate,
                'dozen_count' => max(0, (int) ($sale['dozen_count'] ?? $sale['dozenCount'] ?? 0)),
                'individual_count' => max(0, (int) ($sale['individual_count'] ?? $sale['individualCount'] ?? 0)),
                'total_amount' => max(0, (float) ($sale['total_amount'] ?? $sale['totalAmount'] ?? 0)),
                'paid' => (bool) ($sale['paid'] ?? false),
                'notes' => isset($sale['notes']) ? $this->truncate((string) $sale['notes'], 500) : null,
            ]);
            $imported++;
        }

        if ($unmatchedCustomers > 0) {
            $errors[] = "{$unmatchedCustomers} sale(s) had unmatched customer references — imported without customer link.";
        }

        $this->counts['sales'] = $imported;
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        // Handle ISO 8601 datetime strings (e.g. "2024-01-15T10:30:00.000Z")
        $dateOnly = substr($value, 0, 10);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOnly)) {
            return $dateOnly;
        }

        return null;
    }

    private function truncate(string $value, int $length): string
    {
        return mb_substr($value, 0, $length);
    }
}
