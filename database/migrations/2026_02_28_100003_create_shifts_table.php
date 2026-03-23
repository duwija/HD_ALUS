<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // Shift Pagi, Shift Sore, dll
            $table->time('start_time');                       // 08:00:00
            $table->time('end_time');                         // 17:00:00
            $table->integer('late_tolerance')->default(15);   // Toleransi terlambat (menit)
            $table->string('color', 20)->default('#3498db');  // Warna untuk kalender
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shifts');
    }
}
