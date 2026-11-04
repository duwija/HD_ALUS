<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampiran / bukti transaksi akuntansi (DB tenant).
 * Dikaitkan ke jurnals.code (satu transaksi = beberapa baris jurnal dengan code yang sama).
 * File disimpan privat di storage/tenants/{RESCODE}/app/jurnal/... (path relatif di kolom path).
 */
class CreateJurnalAttachmentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('jurnal_attachments')) {
            return;
        }

        Schema::create('jurnal_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->index();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jurnal_attachments');
    }
}
