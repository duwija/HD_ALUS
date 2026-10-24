<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Titik layanan / POP yang dicakup satu PKS. */
class PksServicePoint extends Model
{
    use SoftDeletes;

    protected $connection = 'isp_master';
    protected $table = 'pks_service_points';

    const JENIS = [
        'pop' => 'POP',
        'titik_layanan' => 'Titik Layanan',
    ];

    protected $fillable = ['pks_id', 'jenis', 'nama', 'kapasitas_mbps', 'lokasi', 'koordinat', 'keterangan'];

    protected $casts = [
        'kapasitas_mbps' => 'integer',
    ];

    /** [lat, lng] dari kolom koordinat "lat,lng", atau null bila kosong/tidak valid. */
    public function getLatLngAttribute(): ?array
    {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', (string) $this->koordinat, $m)) {
            return null;
        }
        [$lat, $lng] = [(float) $m[1], (float) $m[2]];

        return abs($lat) <= 90 && abs($lng) <= 180 ? [$lat, $lng] : null;
    }

    /** Data marker untuk peta (Leaflet) di halaman PKS. */
    public function toMapMarker(): ?array
    {
        if (!$this->lat_lng) {
            return null;
        }

        return [
            'id' => $this->id,
            'lat' => $this->lat_lng[0],
            'lng' => $this->lat_lng[1],
            'jenis' => $this->jenis,
            'jenis_label' => self::JENIS[$this->jenis] ?? $this->jenis,
            'nama' => $this->nama,
            'kapasitas' => $this->kapasitas_mbps,
            'lokasi' => $this->lokasi,
        ];
    }

    public function agreement()
    {
        return $this->belongsTo(PksAgreement::class, 'pks_id');
    }
}
