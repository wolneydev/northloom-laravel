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
        Schema::table('tasks', function (Blueprint $table) {
            // How many delivery attempts have been made for this reminder.
            $table->unsignedTinyInteger('attempts')->default(0)->after('notification_sent_at');

            // Earliest moment a failed (or in-flight) reminder may be retried.
            // Null means "ready as soon as notify_at_datetime has arrived".
            $table->timestamp('next_attempt_at')->nullable()->after('attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'next_attempt_at']);
        });
    }
};
