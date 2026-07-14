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
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 16)->primary();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('department', 255)->nullable();
            $table->string('session', 50)->nullable();
            $table->string('phone', 20);
            $table->string('room_number', 50)->nullable();
            $table->char('hall')->nullable();
            $table->string('password_hash', 255);
            $table->string('otp_code', 6)->nullable();
            $table->timestamp('otp_expiry')->nullable();
            $table->boolean('verified')->default(false);
            $table->string('role', 20)->default('user');
            $table->string('profile_pic', 255)->default('default-avatar.jpg');
            $table->longText('bio')->nullable();
            $table->string('status', 20)->default('unverified');
            $table->text('rejection_reason')->nullable();
            $table->string('verified_by', 50)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('status', 'idx_status');
            $table->index('role', 'idx_role');
            $table->index('created_at', 'idx_created_at');
            $table->fullText(['name', 'email'], 'idx_fulltext_name_email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id', 16)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
