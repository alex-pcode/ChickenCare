<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feed_inventory', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
        });
    }
};
