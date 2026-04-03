<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LicensePlan extends Model
{
    protected $connection = 'isp_master';
    protected $table      = 'license_plans';

    protected $fillable = [
        'name',
        'max_customers',
        'price_monthly',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'max_customers' => 'integer',
        'price_monthly' => 'integer',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'license_plan_id');
    }

    /**
     * Apakah plan ini unlimited pelanggan
     */
    public function isUnlimited(): bool
    {
        return $this->max_customers === -1;
    }

    /**
     * Label limit pelanggan untuk tampilan
     */
    public function maxCustomersLabel(): string
    {
        return $this->isUnlimited() ? 'Unlimited' : number_format($this->max_customers);
    }

    /**
     * Harga dalam format Rupiah
     */
    public function priceFormatted(): string
    {
        return $this->price_monthly === 0
            ? 'Gratis'
            : 'Rp ' . number_format($this->price_monthly, 0, ',', '.');
    }

    public static function allActive()
    {
        return static::where('is_active', true)->orderBy('sort_order')->get();
    }
}
