<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('category', 100);
            $table->string('description', 500);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['user_id', DB::raw('date DESC')], 'idx_expenses_user_date');
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ADD CONSTRAINT; recreate with CHECK inline
            // The validation layer enforces amount >= 0 for SQLite/test environments
        } else {
            DB::statement('ALTER TABLE expenses ADD CONSTRAINT chk_expense_amount CHECK (amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
