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
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'payment_bumdes_enabled')) {
                $table->tinyInteger('payment_bumdes_enabled')->default(1)->after('features');
            }

            if (!Schema::hasColumn('tenants', 'payment_winpay_enabled')) {
                $table->tinyInteger('payment_winpay_enabled')->default(1)->after('payment_bumdes_enabled');
            }

            if (!Schema::hasColumn('tenants', 'payment_tripay_enabled')) {
                $table->tinyInteger('payment_tripay_enabled')->default(1)->after('payment_winpay_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('tenants', 'payment_bumdes_enabled')) {
                $columns[] = 'payment_bumdes_enabled';
            }

            if (Schema::hasColumn('tenants', 'payment_winpay_enabled')) {
                $columns[] = 'payment_winpay_enabled';
            }

            if (Schema::hasColumn('tenants', 'payment_tripay_enabled')) {
                $columns[] = 'payment_tripay_enabled';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
