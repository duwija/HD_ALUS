<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePppoeStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pppoe_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distrouter_id');
            $table->integer('total')->default(0);
            $table->integer('active')->default(0);
            $table->integer('offline')->default(0);
            $table->integer('disabled')->default(0);
            $table->timestamp('collected_at');
            $table->timestamps();

            $table->index(['distrouter_id', 'collected_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pppoe_stats');
    }
}
