<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename columns (idempotent: skip if already renamed)
        if (Schema::hasColumn('feed_inventory', 'name')) {
            Schema::table('feed_inventory', function (Blueprint $table) {
                $table->renameColumn('name', 'brand');
                $table->renameColumn('purchase_date', 'opened_date');
                $table->renameColumn('expiry_date', 'depleted_date');
            });
        }

        // Add new columns (idempotent: skip if already exist)
        if (! Schema::hasColumn('feed_inventory', 'feed_type')) {
            Schema::table('feed_inventory', function (Blueprint $table) {
                $table->string('feed_type')->default('Both')->after('brand');
                $table->string('batch_number', 255)->nullable()->after('total_cost');
            });
        }

        // Create new index, then drop old one (MySQL requires an index for the FK)
        $indexes = collect(Schema::getIndexes('feed_inventory'))->pluck('name')->all();

        if (! in_array('idx_feed_inventory_user_opened', $indexes)) {
            Schema::table('feed_inventory', function (Blueprint $table) {
                $table->index(['user_id', 'opened_date'], 'idx_feed_inventory_user_opened');
            });
        }

        if (in_array('idx_feed_inventory_user', $indexes) && Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('feed_inventory', function (Blueprint $table) {
                $table->dropIndex('idx_feed_inventory_user');
            });
        }
    }

    public function down(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropIndex('idx_feed_inventory_user_opened');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropColumn(['feed_type', 'batch_number']);
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->renameColumn('brand', 'name');
            $table->renameColumn('opened_date', 'purchase_date');
            $table->renameColumn('depleted_date', 'expiry_date');
        });

        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->index(['user_id', 'purchase_date'], 'idx_feed_inventory_user');
        });
    }
};
