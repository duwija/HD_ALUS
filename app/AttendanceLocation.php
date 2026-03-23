<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','address','latitude','longitude','radius','is_active','note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'location_id_in');
    }

    /**
     * Hitung jarak antara koordinat karyawan dengan titik ini (meter, Haversine).
     */
    public function distanceTo(float $lat, float $lng): int
    {
        $earthRadius = 6371000; // meter
        $dLat = deg2rad($lat - $this->latitude);
        $dLng = deg2rad($lng - $this->longitude);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
        return (int) round($earthRadius * 2 * asin(sqrt($a)));
    }

    /**
     * Cek apakah koordinat masih dalam radius izin.
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        return $this->distanceTo($lat, $lng) <= $this->radius;
    }

    /**
     * Cari lokasi terdekat yang valid dari daftar aktif.
     * Return: ['location' => AttendanceLocation, 'distance' => int] atau null jika tidak ada.
     */
    public static function findNearest(float $lat, float $lng): ?array
    {
        $locations = static::where('is_active', true)->get();
        $best = null;

        foreach ($locations as $loc) {
            $dist = $loc->distanceTo($lat, $lng);
            if ($loc->isWithinRadius($lat, $lng)) {
                if (!$best || $dist < $best['distance']) {
                    $best = ['location' => $loc, 'distance' => $dist];
                }
            }
        }
        return $best;
    }
}
