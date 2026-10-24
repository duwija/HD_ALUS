<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PKS (Perjanjian Kerja Sama) satu tenant, di database master.
 * Dikelola di superadmin (/admin/pks), dilihat read-only oleh tenant (/isp/pks).
 */
class PksAgreement extends Model
{
    use SoftDeletes;

    protected $connection = 'isp_master';
    protected $table = 'pks_agreements';

    // Batas "segera berakhir" untuk status & pengingat.
    const EXPIRING_DAYS = 60;

    const JENIS = [
        'baru' => 'PKS Baru',
        'perpanjangan' => 'Perpanjangan',
    ];

    const STATUS = [
        'aktif' => ['label' => 'Aktif', 'badge' => 'success'],
        'segera_berakhir' => ['label' => 'Segera berakhir', 'badge' => 'warning'],
        'berakhir' => ['label' => 'Berakhir', 'badge' => 'danger'],
        'belum_berlaku' => ['label' => 'Belum berlaku', 'badge' => 'info'],
        'diganti' => ['label' => 'Diperpanjang / diganti', 'badge' => 'secondary'],
    ];

    protected $fillable = [
        'tenant_id', 'previous_id', 'jenis', 'nomor_pks', 'nib', 'tanggal_pks',
        'berlaku_mulai', 'berlaku_sampai', 'keterangan',
        'file_path', 'file_name', 'file_size', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'tanggal_pks' => 'date',
        'berlaku_mulai' => 'date',
        'berlaku_sampai' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function previous()
    {
        return $this->belongsTo(self::class, 'previous_id');
    }

    public function successors()
    {
        return $this->hasMany(self::class, 'previous_id');
    }

    public function addenda()
    {
        return $this->hasMany(PksAddendum::class, 'pks_id')->orderBy('tanggal')->orderBy('id');
    }

    public function servicePoints()
    {
        return $this->hasMany(PksServicePoint::class, 'pks_id')->orderBy('jenis')->orderBy('nama');
    }

    /** Masa berlaku akhir setelah memperhitungkan adendum yang mengubahnya (null = tanpa batas). */
    public function getEffectiveEndAttribute(): ?Carbon
    {
        $end = $this->berlaku_sampai;
        foreach ($this->addenda as $addendum) {
            if ($addendum->berlaku_sampai_baru) {
                $end = $addendum->berlaku_sampai_baru;
            }
        }

        return $end;
    }

    public function getStatusAttribute(): string
    {
        if ($this->successors->isNotEmpty()) {
            return 'diganti';
        }

        $today = Carbon::today();
        if ($this->berlaku_mulai && $this->berlaku_mulai->gt($today)) {
            return 'belum_berlaku';
        }

        $end = $this->effective_end;
        if ($end === null) {
            return 'aktif';
        }
        if ($end->lt($today)) {
            return 'berakhir';
        }

        return $today->diffInDays($end) <= self::EXPIRING_DAYS ? 'segera_berakhir' : 'aktif';
    }

    /** Sisa hari sampai masa berlaku efektif berakhir (negatif = sudah lewat, null = tanpa batas). */
    public function getDaysLeftAttribute(): ?int
    {
        $end = $this->effective_end;

        return $end ? Carbon::today()->diffInDays($end, false) : null;
    }

    public function getTotalCapacityAttribute(): int
    {
        return (int) $this->servicePoints->sum('kapasitas_mbps');
    }
}
