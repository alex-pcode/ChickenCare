<?php

namespace Database\Seeders;

use App\Models\EggEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EggEntrySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $today = Carbon::today();

            for ($i = 0; $i < 90; $i++) {
                $date = $today->copy()->subDays($i);

                EggEntry::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                ]);
            }
        }
    }
}
