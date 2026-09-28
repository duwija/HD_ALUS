<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTierToTenantsTable extends Migration
{
    public function up()
    {
        if (!Schema::connection('isp_master')->hasTable('tenants')) {
            return;
        }

        if (!Schema::connection('isp_master')->hasColumn('tenants', 'tier')) {
            Schema::connection('isp_master')->table('tenants', function (Blueprint $table) {
                $table->unsignedTinyInteger('tier')->nullable()->after('license_expires_at');
            });
        }
    }

    public function down()
    {
        if (Schema::connection('isp_master')->hasColumn('tenants', 'tier')) {
            Schema::connection('isp_master')->table('tenants', function (Blueprint $table) {
                $table->dropColumn('tier');
            });
        }
    }
}
