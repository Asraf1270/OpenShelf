<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remember_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 16);
            $table->string('token', 64);
            $table->integer('expiry');
            $table->timestamp('created_at')->useCurrent();
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remember_tokens');
    }
};
