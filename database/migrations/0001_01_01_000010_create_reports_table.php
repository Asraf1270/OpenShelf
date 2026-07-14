<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('user_id', 16)->nullable();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('type', 50)->default('other');
            $table->string('subject', 255);
            $table->longText('message');
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('resolved_by', 50)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('status', 'idx_reports_status');
            $table->index('type', 'idx_reports_type');
            $table->index('user_id', 'idx_reports_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
