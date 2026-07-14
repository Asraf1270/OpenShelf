<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('title', 255);
            $table->string('author', 255);
            $table->longText('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('condition', 50)->nullable();
            $table->string('cover_image', 255)->nullable();
            $table->string('owner_id', 16);
            $table->char('hall')->nullable();
            $table->string('owner_name', 255)->nullable();
            $table->string('status', 20)->default('available');
            $table->integer('views')->default(0);
            $table->integer('times_borrowed')->default(0);
            $table->string('isbn', 20)->nullable();
            $table->string('publication_year', 10)->nullable();
            $table->string('publisher', 255)->nullable();
            $table->string('pages', 20)->nullable();
            $table->string('language', 50)->default('English');
            $table->longText('tags')->nullable()->comment('JSON string');
            $table->longText('reviews')->nullable()->comment('JSON string');
            $table->longText('comments')->nullable()->comment('JSON string');
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->string('status_updated_by', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('hall', 'idx_books_hall');
            $table->index('status', 'idx_status');
            $table->index('category', 'idx_category');
            $table->index('created_at', 'idx_created_at');
            $table->fullText(['title', 'author'], 'idx_fulltext_title_author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
