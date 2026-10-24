<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manajemen PKS (Perjanjian Kerja Sama) per tenant, di database master.
 * tenant_id adalah integer biasa (tanpa FK) ke isp_master.tenants.
 *
 * - pks_agreements: dokumen PKS. previous_id menunjuk PKS lama yang
 *   diperpanjang / digantikan oleh PKS ini.
 * - pks_addenda: adendum atas satu PKS (opsional mengubah masa berlaku).
 * - pks_service_points: titik layanan / POP yang dicakup PKS.
 */
class CreatePksTables extends Migration
{
    public function up()
    {
        $schema = Schema::connection('isp_master');

        if (!$schema->hasTable('pks_agreements')) {
            $schema->create('pks_agreements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('previous_id')->nullable()->index();
                $table->string('jenis', 20)->default('baru'); // baru | perpanjangan
                $table->string('nomor_pks', 150);
                $table->string('nib', 50)->nullable();
                $table->date('tanggal_pks')->nullable();
                $table->date('berlaku_mulai');
                $table->date('berlaku_sampai')->nullable(); // null = tanpa batas waktu
                $table->text('keterangan')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema->hasTable('pks_addenda')) {
            $schema->create('pks_addenda', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pks_id')->index();
                $table->string('nomor', 150);
                $table->date('tanggal')->nullable();
                $table->date('berlaku_sampai_baru')->nullable(); // diisi jika adendum mengubah masa berlaku
                $table->text('keterangan')->nullable();
                $table->string('file_path')->nullable();
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema->hasTable('pks_service_points')) {
            $schema->create('pks_service_points', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pks_id')->index();
                $table->string('jenis', 20)->default('pop'); // pop | titik_layanan
                $table->string('nama', 150);
                $table->unsignedInteger('kapasitas_mbps')->default(0);
                $table->string('lokasi')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        $schema = Schema::connection('isp_master');
        $schema->dropIfExists('pks_service_points');
        $schema->dropIfExists('pks_addenda');
        $schema->dropIfExists('pks_agreements');
    }
}
