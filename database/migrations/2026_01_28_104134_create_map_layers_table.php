<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapLayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_layers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Nama layer
            $table->string('type'); // 'polyline', 'polygon', 'rectangle', 'circle'
            $table->text('coordinates'); // JSON coordinates
            $table->string('color')->default('#3388ff'); // Warna layer
            $table->integer('weight')->default(3); // Ketebalan garis
            $table->decimal('opacity', 3, 2)->default(0.6); // Transparansi
            $table->text('description')->nullable(); // Deskripsi/catatan
            $table->decimal('distance', 10, 2)->nullable(); // Jarak untuk polyline (meter)
            $table->decimal('area', 15, 2)->nullable(); // Luas untuk polygon (meter persegi)
            $table->unsignedBigInteger('created_by')->nullable(); // User yang membuat
            $table->boolean('is_visible')->default(true); // Visibilitas layer
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map_layers');
    }
}
