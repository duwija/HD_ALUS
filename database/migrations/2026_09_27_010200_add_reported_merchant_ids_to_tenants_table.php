<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Merchant IDs (from the tenant's own 'merchants' table) that supervisor
 * accounts are scoped to for this tenant. Null/empty = not configured yet.
 */
class AddReportedMerchantIdsToTenantsTable extends Migration
{
    public function up()
    {
        if (!Schema::connection('isp_master')->hasTable('tenants')) {
            return;
        }

        if (!Schema::connection('isp_master')->hasColumn('tenants', 'reported_merchant_ids')) {
            Schema::connection('isp_master')->table('tenants', function (Blueprint $table) {
                $table->json('reported_merchant_ids')->nullable()->after('features');
            });
        }
    }

    public function down()
    {
        if (Schema::connection('isp_master')->hasColumn('tenants', 'reported_merchant_ids')) {
            Schema::connection('isp_master')->table('tenants', function (Blueprint $table) {
                $table->dropColumn('reported_merchant_ids');
            });
        }
    }
}
