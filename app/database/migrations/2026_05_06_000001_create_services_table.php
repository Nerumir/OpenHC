<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->string('host');
            $table->unsignedSmallInteger('port')->nullable();
            $table->enum('protocol', ['tcp', 'http', 'https', 'ssh', 'rdp', 'udp', 'database', 'ftp', 'ftps', 'smtp', 'smtps', 'icmp', 'irc', 'smb', 'ldap', 'ldaps']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
