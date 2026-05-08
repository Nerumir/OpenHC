<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['up', 'down']);
            $table->float('response_time')->nullable();
            $table->string('protocol_detail', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['service_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_checks');
    }
};
