<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smtp_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('notification_interval_minutes')->default(60)->after('from_name');
            $table->timestamp('last_notified_at')->nullable()->after('notification_interval_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('smtp_settings', function (Blueprint $table) {
            $table->dropColumn(['notification_interval_minutes', 'last_notified_at']);
        });
    }
};
