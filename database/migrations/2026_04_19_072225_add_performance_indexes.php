<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('death_records', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'idx_death_records_user_date');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->index(['user_id', 'depleted_date'], 'idx_feed_inventory_depleted');
        });

        Schema::table('flock_batches', function (Blueprint $table) {
            $table->index(['user_id', 'acquisition_date'], 'idx_flock_batches_acquisition');
        });

        Schema::table('batch_events', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'idx_batch_events_user_date');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['customer_id', 'sale_date'], 'idx_sales_customer_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('death_records', function (Blueprint $table) {
            $table->dropIndex('idx_death_records_user_date');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropIndex('idx_feed_inventory_depleted');
        });

        Schema::table('flock_batches', function (Blueprint $table) {
            $table->dropIndex('idx_flock_batches_acquisition');
        });

        Schema::table('batch_events', function (Blueprint $table) {
            $table->dropIndex('idx_batch_events_user_date');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_customer_date');
        });
    }
};
