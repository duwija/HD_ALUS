<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsMockToAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_mock_in')->default(false)->after('device_info')
                  ->comment('True jika GPS clock-in terdeteksi fake/mock');
            $table->boolean('is_mock_out')->default(false)->nullable()->after('is_mock_in')
                  ->comment('True jika GPS clock-out terdeteksi fake/mock');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['is_mock_in', 'is_mock_out']);
        });
    }
}
