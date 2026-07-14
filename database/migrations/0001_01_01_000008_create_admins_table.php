<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255);
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('role', 20)->default('admin');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
