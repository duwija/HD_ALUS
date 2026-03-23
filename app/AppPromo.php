<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppPromo extends Model
{
    protected $table = 'app_promos';

    protected $fillable = [
        'title', 'content', 'image_url', 'badge',
        'is_active', 'start_date', 'end_date', 'created_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * Scope: hanya promo yang aktif dan dalam rentang tanggal
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }
}
