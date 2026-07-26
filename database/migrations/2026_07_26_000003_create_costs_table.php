<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('description');
            $table->date('incurred_on');
            $table->timestamps();
            $table->index(['project_id', 'incurred_on']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE costs ADD CONSTRAINT costs_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('costs');
    }
};
