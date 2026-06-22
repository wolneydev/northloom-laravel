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
            if (! Schema::hasColumn('tasks', 'notify_at_datetime')) {
                $table->dateTime('notify_at_datetime')->nullable()->after('notify');
            }

            if (Schema::hasColumn('tasks', 'notify_minutes_before')) {
                $table->dropColumn('notify_minutes_before');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'notify_minutes_before')) {
                $table->unsignedInteger('notify_minutes_before')->nullable()->after('notify');
            }

            if (Schema::hasColumn('tasks', 'notify_at_datetime')) {
                $table->dropColumn('notify_at_datetime');
            }
        });
    }
};
