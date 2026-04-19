<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            EggEntrySeeder::class,
            ExpenseSeeder::class,
            FeedInventorySeeder::class,
            FlockProfileSeeder::class,
            FlockEventSeeder::class,
            FlockBatchSeeder::class,
            BatchEventSeeder::class,
            DeathRecordSeeder::class,
            CustomerSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
