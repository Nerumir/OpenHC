<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->enum('encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('from_address');
            $table->string('from_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
