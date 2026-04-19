<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('death_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('flock_batches')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('count');
            $table->enum('cause', ['predator', 'disease', 'age', 'injury', 'unknown', 'culled', 'other']);
            $table->string('description', 500);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'date'], 'idx_death_records_batch');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE death_records ADD CONSTRAINT chk_death_count CHECK (count > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('death_records');
    }
};
