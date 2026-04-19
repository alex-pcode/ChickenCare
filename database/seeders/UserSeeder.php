<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Free User',
            'email' => 'free@example.com',
            'tier' => 'free',
        ]);

        User::factory()->premium()->create([
            'name' => 'Premium User',
            'email' => 'premium@example.com',
        ]);
    }
}
