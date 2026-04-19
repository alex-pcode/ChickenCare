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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('egg_price', 6, 2)->nullable()->default(0.30)->after('yearly_egg_goal');
            $table->string('chicken_goal')->nullable()->default('hobby')->after('egg_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['egg_price', 'chicken_goal']);
        });
    }
};
