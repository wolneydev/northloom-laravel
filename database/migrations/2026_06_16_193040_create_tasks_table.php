<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('task_date');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('notify')->default(false);
            $table->dateTime('notify_at_datetime')->nullable();
            $table->timestamps();

            // Calendar queries filter by owner and day range, so index both.
            $table->index(['user_id', 'task_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
