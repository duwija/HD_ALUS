<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_customer');
            $table->string('updated_by', 100); // User yang melakukan update
            $table->string('field_changed', 50); // Field apa yang berubah
            $table->text('old_value')->nullable(); // Nilai lama
            $table->text('new_value')->nullable(); // Nilai baru
            $table->text('notes')->nullable(); // Catatan saat update
            $table->timestamps();
            
            // Foreign key
            $table->foreign('id_customer')->references('id')->on('customers')->onDelete('cascade');
            
            // Index untuk performa
            $table->index('id_customer');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lead_updates');
    }
}
