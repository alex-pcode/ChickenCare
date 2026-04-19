<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('flock_batches')->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', [
                'health_check', 'vaccination', 'relocation', 'breeding',
                'laying_start', 'brooding_start', 'brooding_stop',
                'production_note', 'flock_added', 'flock_loss', 'other',
            ]);
            $table->string('description', 500);
            $table->unsignedInteger('affected_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'date'], 'idx_batch_events_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_events');
    }
};
