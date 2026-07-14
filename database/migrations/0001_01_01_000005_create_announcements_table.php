<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('title', 255);
            $table->longText('content');
            $table->string('priority', 20)->default('info');
            $table->string('target', 50)->default('all');
            $table->string('created_by', 50)->nullable();
            $table->string('created_by_name', 255)->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->longText('sent_via')->nullable()->comment('JSON string');
            $table->longText('stats')->nullable()->comment('JSON string');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
