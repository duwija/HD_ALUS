<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PksAddendum extends Model
{
    use SoftDeletes;

    protected $connection = 'isp_master';
    protected $table = 'pks_addenda';

    protected $fillable = [
        'pks_id', 'nomor', 'tanggal', 'berlaku_sampai_baru', 'keterangan',
        'file_path', 'file_name', 'file_size', 'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'berlaku_sampai_baru' => 'date',
    ];

    public function agreement()
    {
        return $this->belongsTo(PksAgreement::class, 'pks_id');
    }
}
