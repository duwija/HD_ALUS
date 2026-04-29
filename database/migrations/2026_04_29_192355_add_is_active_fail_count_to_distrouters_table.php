<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveFailCountToDistroutersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('distrouters', function (Blueprint $table) {
            if (!Schema::hasColumn('distrouters', 'is_active')) {
                $table->tinyInteger('is_active')->default(1)->after('password');
            }
            if (!Schema::hasColumn('distrouters', 'fail_count')) {
                $table->integer('fail_count')->default(0)->after('is_active');
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
        Schema::table('distrouters', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'fail_count']);
        });
    }
}
