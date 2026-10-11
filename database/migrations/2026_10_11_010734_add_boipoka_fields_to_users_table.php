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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'boipoka_points')) {
                $table->integer('boipoka_points')->default(0)->after('status');
            }

            if (!Schema::hasColumn('users', 'boipoka_badge')) {
                $table->boolean('boipoka_badge')->default(false)->after('boipoka_points');
            }

            if (!Schema::hasColumn('users', 'boipoka_position')) {
                $table->unsignedInteger('boipoka_position')->nullable()->after('boipoka_badge');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'boipoka_position')) {
                $table->dropColumn('boipoka_position');
            }

            if (Schema::hasColumn('users', 'boipoka_badge')) {
                $table->dropColumn('boipoka_badge');
            }

            if (Schema::hasColumn('users', 'boipoka_points')) {
                $table->dropColumn('boipoka_points');
            }
        });
    }
};
