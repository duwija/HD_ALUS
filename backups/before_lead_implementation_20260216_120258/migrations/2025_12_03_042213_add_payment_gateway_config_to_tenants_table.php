<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentGatewayConfigToTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->tinyInteger('payment_bumdes_enabled')->default(1)->after('features');
            $table->tinyInteger('payment_winpay_enabled')->default(1)->after('payment_bumdes_enabled');
            $table->tinyInteger('payment_tripay_enabled')->default(1)->after('payment_winpay_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['payment_bumdes_enabled', 'payment_winpay_enabled', 'payment_tripay_enabled']);
        });
    }
}
