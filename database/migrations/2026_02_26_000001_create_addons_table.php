<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddonsTable extends Migration
{
    public function up()
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('price')->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('id_customer');
            $table->unsignedBigInteger('id_addon');
            $table->timestamps();
            $table->foreign('id_addon')->references('id')->on('addons')->onDelete('cascade');
            $table->unique(['id_customer', 'id_addon']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_addons');
        Schema::dropIfExists('addons');
    }
}
