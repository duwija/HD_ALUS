<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentGatewaysTable extends Migration
{
    public function up()
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();

            // Tenant identifier (sama dengan tenants.domain)
            $table->string('domain');

            // Nama internal provider: xendit | winpay | tripay | bumdes | duitku | oy | dll
            $table->string('provider', 50);

            // Label tampil ke customer
            $table->string('label')->default('');

            // Ikon: Font Awesome class atau nama file gambar
            $table->string('icon')->default('fa-credit-card');

            // On / Off per tenant
            $table->tinyInteger('enabled')->default(1);

            // Biaya tambahan
            // fee_type: none | fixed | percent
            $table->enum('fee_type', ['none', 'fixed', 'percent'])->default('none');
            $table->decimal('fee_amount', 12, 2)->default(0);   // Nominal rupiah atau % (misal: 2.5 = 2.5%)
            $table->string('fee_label')->default('Biaya Admin'); // Label di invoice

            // Urutan tampil di halaman invoice
            $table->smallInteger('sort_order')->default(0);

            // Konfigurasi khusus provider (channel list, API key override, dll)
            // Contoh: {"channels": ["BRIVA","MANDIRIVA"], "api_key": "...", "secret": "..."}
            $table->json('settings')->nullable();

            $table->timestamps();

            // Satu provider unik per tenant
            $table->unique(['domain', 'provider']);
            $table->index('domain');
            $table->index(['domain', 'enabled']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_gateways');
    }
}
