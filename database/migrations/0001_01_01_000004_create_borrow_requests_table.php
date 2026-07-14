<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_requests', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('book_id', 10);
            $table->string('book_title', 255)->nullable();
            $table->string('book_author', 255)->nullable();
            $table->string('book_cover', 255)->nullable();
            $table->string('owner_id', 16);
            $table->string('owner_name', 255)->nullable();
            $table->string('owner_email', 255)->nullable();
            $table->string('borrower_id', 16);
            $table->string('borrower_name', 255)->nullable();
            $table->string('borrower_email', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('request_date')->useCurrent();
            $table->timestamp('expected_return_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->text('message')->nullable();
            $table->longText('history')->nullable()->comment('JSON string');
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 50)->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_by', 50)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('actual_return_date')->nullable();
            $table->string('return_condition', 50)->nullable();
            $table->string('returned_by', 50)->nullable();
            $table->string('returned_by_name', 255)->nullable();
            $table->integer('rating')->default(0);
            $table->string('return_confirmation_token', 64)->nullable()->comment('Secure token emailed to owner');
            $table->string('return_confirmation_status', 20)->nullable()->comment('pending_owner | confirmed | rejected');
            $table->timestamp('return_confirmation_sent_at')->nullable();
            $table->timestamp('return_confirmed_at')->nullable();
            $table->timestamp('return_rejected_at')->nullable();
            $table->text('return_reject_reason')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('borrower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('status', 'idx_status');
            $table->index('request_date', 'idx_request_date');
            $table->index('return_confirmation_token', 'idx_return_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_requests');
    }
};
