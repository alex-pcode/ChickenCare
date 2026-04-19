<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->decimal('quantity', 10, 2)->default(0.00);
            $table->enum('unit', ['kg', 'lbs']);
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', DB::raw('purchase_date DESC')], 'idx_feed_inventory_user');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ADD CONSTRAINT; validation layer enforces in test environments
        } else {
            DB::statement('ALTER TABLE feed_inventory ADD CONSTRAINT chk_feed_quantity CHECK (quantity >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_inventory');
    }
};
