<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentUrlToPaymentBundles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_bundles', function (Blueprint $table) {
            $table->string('payment_url', 1000)->nullable()->after('tripay_method');
        });
    }

    public function down()
    {
        Schema::table('payment_bundles', function (Blueprint $table) {
            $table->dropColumn('payment_url');
        });
    }
}
