<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceTypeToTicketdetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticketdetails', function (Blueprint $table) {
            $table->string('device_type', 10)->nullable()->after('coordinate')->comment('M=Mobile, D=Desktop');
        });

        // Migrate existing data: pisahkan coordinate|device_type yang sudah tersimpan
        \DB::table('ticketdetails')
            ->whereNotNull('coordinate')
            ->where('coordinate', 'like', '%|%')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    [$coord, $device] = explode('|', $row->coordinate, 2);
                    \DB::table('ticketdetails')->where('id', $row->id)->update([
                        'coordinate'  => trim($coord),
                        'device_type' => trim($device),
                    ]);
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
        Schema::table('ticketdetails', function (Blueprint $table) {
            $table->dropColumn('device_type');
        });
    }
}
