<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('sale_date');
            $table->unsignedInteger('dozen_count')->default(0);
            $table->unsignedInteger('individual_count')->default(0);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->boolean('paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_total_amount CHECK (total_amount >= 0)');
            DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_dozen_count CHECK (dozen_count >= 0)');
            DB::statement('ALTER TABLE sales ADD CONSTRAINT chk_sales_individual_count CHECK (individual_count >= 0)');
            Schema::table('sales', function (Blueprint $table) {
                $table->index(['user_id', DB::raw('sale_date DESC')], 'idx_sales_user_date');
                $table->index('customer_id', 'idx_sales_customer');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
