<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLostLeadFieldsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('lost_at')->nullable()->after('converted_by');
            $table->string('lost_reason', 100)->nullable()->after('lost_at');
            $table->text('lost_notes')->nullable()->after('lost_reason');
            $table->unsignedBigInteger('lost_by')->nullable()->after('lost_notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['lost_at', 'lost_reason', 'lost_notes', 'lost_by']);
        });
    }
}
