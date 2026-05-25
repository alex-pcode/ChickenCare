<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ALTER COLUMN for enums; we use a column-swap approach.
        Schema::table('flock_events', function (Blueprint $table) {
            $table->string('type_new', 50)->nullable();
        });

        DB::statement('UPDATE flock_events SET type_new = type');

        Schema::table('flock_events', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('flock_events', function (Blueprint $table) {
            $table->enum('type', ['acquisition', 'laying_start', 'broody', 'hatching', 'recount', 'other'])
                ->default('other');
        });

        DB::statement('UPDATE flock_events SET type = type_new');

        Schema::table('flock_events', function (Blueprint $table) {
            $table->dropColumn('type_new');
        });
    }

    public function down(): void
    {
        Schema::table('flock_events', function (Blueprint $table) {
            $table->string('type_new', 50)->nullable();
        });

        DB::statement("UPDATE flock_events SET type_new = CASE WHEN type = 'recount' THEN 'other' ELSE type END");

        Schema::table('flock_events', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('flock_events', function (Blueprint $table) {
            $table->enum('type', ['acquisition', 'laying_start', 'broody', 'hatching', 'other'])
                ->default('other');
        });

        DB::statement('UPDATE flock_events SET type = type_new');

        Schema::table('flock_events', function (Blueprint $table) {
            $table->dropColumn('type_new');
        });
    }
};
