<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'group',
        'coordinates',
        'color',
        'icon',
        'weight',
        'opacity',
        'description',
        'distance',
        'area',
        'created_by',
        'is_visible'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'opacity' => 'float',
        'distance' => 'float',
        'area' => 'float'
    ];

    /**
     * Relationship dengan User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Set coordinates from array
     */
    public function setCoordinatesAttribute($value)
    {
        $this->attributes['coordinates'] = is_array($value) ? json_encode($value) : $value;
    }
}
