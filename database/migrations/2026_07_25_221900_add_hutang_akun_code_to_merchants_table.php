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
        if (!Schema::hasTable('merchants')) {
            return;
        }

        Schema::table('merchants', function (Blueprint $table) {
            if (!Schema::hasColumn('merchants', 'hutang_akun_code')) {
                $table->string('hutang_akun_code')->nullable()->after('akun_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('merchants')) {
            return;
        }

        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'hutang_akun_code')) {
                $table->dropColumn('hutang_akun_code');
            }
        });
    }
};
