<?php

namespace Database\Seeders;

use App\Models\BatchEvent;
use App\Models\FlockBatch;
use Illuminate\Database\Seeder;

class BatchEventSeeder extends Seeder
{
    public function run(): void
    {
        $batches = FlockBatch::with('user')->get();

        foreach ($batches as $batch) {
            BatchEvent::factory()
                ->count(fake()->numberBetween(5, 10))
                ->for($batch, 'flockBatch')
                ->create([
                    'user_id' => $batch->user_id,
                    'date' => fn () => fake()->dateTimeBetween(
                        $batch->acquisition_date,
                        'now'
                    )->format('Y-m-d'),
                ]);
        }
    }
}
