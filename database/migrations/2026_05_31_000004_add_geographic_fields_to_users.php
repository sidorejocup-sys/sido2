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
            // Add fields for geographic and property scoping
            if (!Schema::hasColumn('users', 'nik')) {
                $table->string('nik', 16)->nullable()->index();
            }

            if (!Schema::hasColumn('users', 'rt')) {
                $table->string('rt', 3)->nullable();
            }

            if (!Schema::hasColumn('users', 'rw')) {
                $table->string('rw', 3)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nik')) {
                $table->dropIndex(['nik']);
                $table->dropColumn('nik');
            }

            if (Schema::hasColumn('users', 'rt')) {
                $table->dropColumn('rt');
            }

            if (Schema::hasColumn('users', 'rw')) {
                $table->dropColumn('rw');
            }
        });
    }
};
