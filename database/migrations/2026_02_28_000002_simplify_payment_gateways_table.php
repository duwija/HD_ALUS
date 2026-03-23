<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplifikasi payment_gateways: 
 * - Domain jadi nullable (tidak dipakai karena tabel ada di tiap tenant DB)
 * - Unique key dari (domain, provider) → provider saja
 */
class SimplifyPaymentGatewaysTable extends Migration
{
    public function up()
    {
        // Hapus unique lama (domain + provider)
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropUnique('payment_gateways_domain_provider_unique');
        });

        // Domain jadi nullable via raw SQL (tanpa doctrine/dbal)
        DB::statement('ALTER TABLE payment_gateways MODIFY domain VARCHAR(255) NULL DEFAULT NULL');

        // Tambah unique hanya pada provider
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->unique('provider');
        });
    }

    public function down()
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropUnique(['provider']);
        });
        DB::statement('ALTER TABLE payment_gateways MODIFY domain VARCHAR(255) NOT NULL');
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->unique(['domain', 'provider']);
        });
    }
}
