<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat backup konfigurasi MikroTik (database master, semua tenant).
 * tenant_id -> isp_master.tenants.id, distrouter_id -> distrouters.id di DB tenant (tanpa FK).
 */
class CreateMikrotikBackupsTable extends Migration
{
    public function up()
    {
        $schema = Schema::connection('isp_master');
        if ($schema->hasTable('mikrotik_backups')) {
            return;
        }

        $schema->create('mikrotik_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('distrouter_id');
            $table->string('router_name')->nullable();
            $table->string('status', 20); // ok | unchanged | failed | unsupported
            $table->string('ros_version', 60)->nullable();
            $table->string('file_path')->nullable(); // relatif ke storage/app, terenkripsi
            $table->unsignedInteger('size')->nullable(); // ukuran export (plaintext)
            $table->string('sha256', 64)->nullable(); // isi tanpa baris tanggal, untuk deteksi perubahan
            $table->text('message')->nullable();
            $table->string('triggered_by', 100)->nullable(); // "schedule" atau nama user
            $table->timestamps();
            $table->index(['tenant_id', 'distrouter_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::connection('isp_master')->dropIfExists('mikrotik_backups');
    }
}
