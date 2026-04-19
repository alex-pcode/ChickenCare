<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flock_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('farm_name')->default('My Chicken Farm');
            $table->string('location')->nullable();
            $table->unsignedInteger('flock_size')->default(0);
            $table->string('breed')->nullable();
            $table->date('start_date')->nullable();
            $table->unsignedInteger('hens')->default(0);
            $table->unsignedInteger('roosters')->default(0);
            $table->unsignedInteger('chicks')->default(0);
            $table->unsignedInteger('brooding')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flock_profiles');
    }
};
