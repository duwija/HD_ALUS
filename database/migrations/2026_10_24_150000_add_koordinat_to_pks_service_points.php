<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Koordinat "lat,lng" untuk titik layanan / POP PKS (database master). */
class AddKoordinatToPksServicePoints extends Migration
{
    public function up()
    {
        $schema = Schema::connection('isp_master');
        if ($schema->hasTable('pks_service_points') && !$schema->hasColumn('pks_service_points', 'koordinat')) {
            $schema->table('pks_service_points', function (Blueprint $table) {
                $table->string('koordinat', 60)->nullable()->after('lokasi');
            });
        }
    }

    public function down()
    {
        $schema = Schema::connection('isp_master');
        if ($schema->hasColumn('pks_service_points', 'koordinat')) {
            $schema->table('pks_service_points', function (Blueprint $table) {
                $table->dropColumn('koordinat');
            });
        }
    }
}
