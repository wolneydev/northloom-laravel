<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('opening_balance', 15, 2);
            $table->decimal('available_balance', 15, 2);
            $table->timestamps();
            $table->index('project_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE funds ADD CONSTRAINT funds_opening_balance_nonnegative CHECK (opening_balance >= 0)');
            DB::statement('ALTER TABLE funds ADD CONSTRAINT funds_available_balance_nonnegative CHECK (available_balance >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};
