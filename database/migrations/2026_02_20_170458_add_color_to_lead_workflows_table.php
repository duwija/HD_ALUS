<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorToLeadWorkflowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lead_workflows', function (Blueprint $table) {
            $table->string('color', 30)->default('secondary')->after('description');
        });

        // Set default colors for existing stages by order
        $colors = [1=>'secondary', 2=>'info', 3=>'primary', 4=>'warning', 5=>'success', 6=>'danger', 7=>'dark'];
        foreach ($colors as $order => $color) {
            \Illuminate\Support\Facades\DB::table('lead_workflows')->where('order', $order)->update(['color' => $color]);
        }
    }

    public function down()
    {
        Schema::table('lead_workflows', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
}
