<?php

namespace Database\Seeders;

use App\Models\FlockEvent;
use App\Models\FlockProfile;
use Illuminate\Database\Seeder;

class FlockEventSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = FlockProfile::all();

        foreach ($profiles as $profile) {
            $eventCount = fake()->numberBetween(5, 10);

            FlockEvent::factory()
                ->count($eventCount)
                ->create([
                    'flock_profile_id' => $profile->id,
                ]);
        }
    }
}
