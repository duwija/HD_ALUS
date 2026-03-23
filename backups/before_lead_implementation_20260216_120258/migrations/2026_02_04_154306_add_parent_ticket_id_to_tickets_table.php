<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentTicketIdToTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_ticket_id')->nullable()->after('id');
            $table->foreign('parent_ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->string('ticket_type', 50)->default('standalone')->after('parent_ticket_id')->comment('standalone, parent, child');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['parent_ticket_id']);
            $table->dropColumn(['parent_ticket_id', 'ticket_type']);
        });
    }
}
