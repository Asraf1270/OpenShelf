<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_us', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('user_id', 16);
            $table->string('user_name', 255)->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('user_phone', 20)->nullable();
            $table->string('user_department', 255)->nullable();
            $table->string('user_session', 50)->nullable();
            $table->string('user_room', 50)->nullable();
            $table->string('provider', 50);
            $table->string('account_number', 50);
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id', 100);
            $table->string('status', 20)->default('pending');
            $table->string('invoice_number', 50)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 50)->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_us');
    }
};
