<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_portals', function (Blueprint $table) {
            $table->string('screenshot_path')->nullable()->after('last_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('admin_portals', function (Blueprint $table) {
            $table->dropColumn('screenshot_path');
        });
    }
};
