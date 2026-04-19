<?php

namespace Database\Seeders;

use App\Models\FlockProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class FlockProfileSeeder extends Seeder
{
    public function run(): void
    {
        $premiumUsers = User::where('tier', 'premium')
            ->orWhere('is_admin', true)
            ->get();

        foreach ($premiumUsers as $user) {
            if (! $user->flockProfile) {
                FlockProfile::factory()->create([
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
