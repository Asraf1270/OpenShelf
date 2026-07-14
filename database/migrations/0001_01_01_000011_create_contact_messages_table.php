<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('user_id', 16)->nullable();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('subject', 255);
            $table->longText('message');
            $table->string('status', 20)->default('unread');
            $table->text('admin_reply')->nullable();
            $table->string('replied_by', 50)->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('status', 'idx_contact_status');
            $table->index('user_id', 'idx_contact_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
