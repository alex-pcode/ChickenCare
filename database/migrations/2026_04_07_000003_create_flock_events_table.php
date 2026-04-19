<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flock_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flock_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['acquisition', 'laying_start', 'broody', 'hatching', 'other']);
            $table->string('description', 500);
            $table->unsignedInteger('affected_birds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['flock_profile_id', 'date'], 'idx_flock_events_profile_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flock_events');
    }
};
