<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupToMapLayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('map_layers') || Schema::hasColumn('map_layers', 'group')) {
            return;
        }

        Schema::table('map_layers', function (Blueprint $table) {
            $table->string('group')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('map_layers') || !Schema::hasColumn('map_layers', 'group')) {
            return;
        }

        Schema::table('map_layers', function (Blueprint $table) {
            $table->dropColumn('group');
        });
    }
}
