<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoordinateToTicketdetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticketdetails', function (Blueprint $table) {
            // Koordinat GPS saat update/perubahan workflow dilakukan
            $table->string('coordinate', 100)->nullable()->after('updated_by');
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
            $table->dropColumn('coordinate');
        });
    }
}
