<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('support_us_id', 30)->nullable();
            $table->string('user_id', 16);
            $table->string('user_name', 255)->nullable();
            $table->string('user_email', 255)->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id', 100)->nullable();
            $table->string('invoice_number', 50);
            $table->string('status', 20)->default('completed');
            $table->string('created_by', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique('support_us_id', 'support_us_transaction');
            $table->foreign('support_us_id')->references('id')->on('support_us')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
