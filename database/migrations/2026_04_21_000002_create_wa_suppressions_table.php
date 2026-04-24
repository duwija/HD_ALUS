<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaSuppressionsTable extends Migration
{
    public function up()
    {
        Schema::create('wa_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant', 120)->index();
            $table->string('number', 20)->index();
            $table->unsignedInteger('total_failures')->default(0);
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('suppress_until')->nullable()->index();
            $table->string('reason', 100)->nullable();
            $table->timestamps();

            $table->unique(['tenant', 'number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_suppressions');
    }
}
