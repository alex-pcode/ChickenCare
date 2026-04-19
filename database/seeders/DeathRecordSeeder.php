<?php

namespace Database\Seeders;

use App\Models\DeathRecord;
use App\Models\FlockBatch;
use Illuminate\Database\Seeder;

class DeathRecordSeeder extends Seeder
{
    public function run(): void
    {
        $batches = FlockBatch::with('user')->get();

        foreach ($batches as $batch) {
            $totalDeaths = 0;
            $maxDeaths = (int) floor($batch->initial_count * 0.3);
            $recordCount = fake()->numberBetween(2, min(5, $maxDeaths));

            for ($i = 0; $i < $recordCount; $i++) {
                $remaining = $maxDeaths - $totalDeaths;
                if ($remaining <= 0) {
                    break;
                }

                $count = fake()->numberBetween(1, min(3, $remaining));
                $totalDeaths += $count;

                DeathRecord::factory()
                    ->for($batch, 'flockBatch')
                    ->create([
                        'user_id' => $batch->user_id,
                        'count' => $count,
                        'date' => fake()->dateTimeBetween(
                            $batch->acquisition_date,
                            'now'
                        )->format('Y-m-d'),
                    ]);
            }

            if ($totalDeaths > 0) {
                $batch->decrement('current_count', $totalDeaths);
            }
        }
    }
}
