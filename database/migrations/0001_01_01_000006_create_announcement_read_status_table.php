<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_read_status', function (Blueprint $table) {
            $table->id();
            $table->string('announcement_id', 50);
            $table->string('user_id', 16);
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['announcement_id', 'user_id'], 'announce_user');
            $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_read_status');
    }
};
