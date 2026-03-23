<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentBundlesTables extends Migration
{
    public function up()
    {
        Schema::create('payment_bundles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bundle_ref', 100)->unique();   // MULTI-{custId}-{hex}
            $table->string('gateway', 50);                 // duitku | tripay | winpay
            $table->unsignedBigInteger('id_customer');
            $table->unsignedBigInteger('total_amount');    // sum of invoice total_amount (before fee)
            $table->unsignedBigInteger('paid_amount')->default(0); // amount received from gateway
            $table->tinyInteger('status')->default(0);     // 0=pending, 1=paid, 2=expired
            $table->string('tripay_method', 50)->nullable(); // untuk Tripay: BRIVA, BCAVA, dll
            $table->timestamps();

            $table->index('id_customer');
            $table->index('status');
        });

        Schema::create('payment_bundle_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bundle_ref', 100);
            $table->unsignedBigInteger('suminvoice_id');

            $table->index('bundle_ref');
            $table->index('suminvoice_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_bundle_items');
        Schema::dropIfExists('payment_bundles');
    }
}
