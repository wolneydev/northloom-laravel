<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->dateTime('allocated_at');
            $table->timestamps();
            $table->index(['task_id', 'allocated_at']);
            $table->index(['fund_id', 'allocated_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE financial_allocations ADD CONSTRAINT financial_allocations_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_allocations');
    }
};
