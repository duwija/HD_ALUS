<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** Riwayat backup konfigurasi MikroTik (isp_master), lihat App\Services\MikrotikConfigBackup. */
class MikrotikBackup extends Model
{
    protected $connection = 'isp_master';
    protected $table = 'mikrotik_backups';

    protected $fillable = [
        'tenant_id', 'distrouter_id', 'router_name', 'status', 'ros_version',
        'file_path', 'size', 'sha256', 'message', 'triggered_by',
    ];

    const STATUS = [
        'ok'          => ['label' => 'Tersimpan', 'badge' => 'success'],
        'unchanged'   => ['label' => 'Tidak berubah', 'badge' => 'secondary'],
        'failed'      => ['label' => 'Gagal', 'badge' => 'danger'],
        'unsupported' => ['label' => 'Perlu RouterOS ≥ 7.13', 'badge' => 'warning'],
    ];
}
