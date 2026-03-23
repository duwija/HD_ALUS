<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMikrotikSyncFailuresTable extends Migration
{
    public function up()
    {
        Schema::create('mikrotik_sync_failures', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();          // snapshot nama saat gagal
            $table->string('customer_cid')->nullable();           // customer_id string

            $table->string('action', 20)->default('disable');     // disable | enable | remove
            $table->string('pppoe')->nullable();                  // PPPoE username

            $table->unsignedBigInteger('id_distrouter')->nullable();
            $table->string('distrouter_ip')->nullable();          // snapshot IP router saat gagal

            $table->text('error_message')->nullable();
            $table->tinyInteger('attempts')->default(1);

            // pending = belum ditangani, retrying = sedang retry, resolved = sudah selesai
            $table->enum('status', ['pending', 'retrying', 'resolved'])->default('pending')->index();

            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();
            $table->text('notes')->nullable();                    // catatan manual admin

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mikrotik_sync_failures');
    }
}
