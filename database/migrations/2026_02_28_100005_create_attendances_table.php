<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('location_id_in')->nullable();   // Lokasi saat clock-in
            $table->unsignedBigInteger('location_id_out')->nullable();  // Lokasi saat clock-out

            // Clock In
            $table->time('clock_in')->nullable();
            $table->decimal('lat_in', 10, 7)->nullable();
            $table->decimal('lng_in', 10, 7)->nullable();
            $table->string('photo_in')->nullable();                      // path foto selfie
            $table->integer('distance_in')->nullable();                  // jarak dari titik absen (meter)

            // Clock Out
            $table->time('clock_out')->nullable();
            $table->decimal('lat_out', 10, 7)->nullable();
            $table->decimal('lng_out', 10, 7)->nullable();
            $table->string('photo_out')->nullable();
            $table->integer('distance_out')->nullable();

            // Status & Meta
            $table->enum('status', ['present','late','absent','leave','holiday','off'])->default('absent');
            $table->integer('late_minutes')->default(0);                 // menit keterlambatan
            $table->integer('work_minutes')->nullable();                 // total jam kerja (menit)
            $table->string('device_info', 200)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id','date']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            $table->foreign('location_id_in')->references('id')->on('attendance_locations')->onDelete('set null');
            $table->foreign('location_id_out')->references('id')->on('attendance_locations')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
