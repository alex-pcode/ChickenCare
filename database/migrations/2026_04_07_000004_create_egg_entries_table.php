<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egg_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('count')->default(0);
            $table->enum('size', ['small', 'medium', 'large', 'extra-large', 'jumbo'])->nullable();
            $table->enum('color', ['white', 'brown', 'blue', 'green', 'speckled', 'cream'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date'], 'idx_egg_entries_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egg_entries');
    }
};
