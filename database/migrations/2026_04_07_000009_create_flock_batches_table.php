<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('batch_name');
            $table->string('breed');
            $table->date('acquisition_date');
            $table->unsignedInteger('initial_count');
            $table->unsignedInteger('current_count')->default(0);
            $table->unsignedInteger('hens_count')->default(0);
            $table->unsignedInteger('roosters_count')->default(0);
            $table->unsignedInteger('chicks_count')->default(0);
            $table->unsignedInteger('brooding_count')->default(0);
            $table->enum('type', ['hens', 'roosters', 'chicks', 'mixed']);
            $table->enum('age_at_acquisition', ['chick', 'juvenile', 'adult']);
            $table->date('expected_laying_start_date')->nullable();
            $table->date('actual_laying_start_date')->nullable();
            $table->string('source');
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('user_id', 'idx_flock_batches_user');
            $table->index(['user_id', 'is_active'], 'idx_flock_batches_active');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE flock_batches ADD CONSTRAINT chk_batch_initial CHECK (initial_count > 0)');
            DB::statement('ALTER TABLE flock_batches ADD CONSTRAINT chk_batch_current CHECK (current_count >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flock_batches');
    }
};
