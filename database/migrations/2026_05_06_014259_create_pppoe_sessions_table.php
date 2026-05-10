<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePppoeSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pppoe_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distrouter_id')->index();
            $table->string('pppoe_name')->index();
            $table->boolean('is_online')->default(false)->index();
            $table->timestamp('last_offline_at')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['distrouter_id', 'pppoe_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pppoe_sessions');
    }
}
