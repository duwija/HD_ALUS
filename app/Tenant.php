<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'isp_master';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'domain',
        'app_name',
        'signature',
        'rescode',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password',
        'mail_from',
        'whatsapp_token',
        'xendit_key',
        'features',
        'env_variables',
        'payment_bumdes_enabled',
        'payment_winpay_enabled',
        'payment_tripay_enabled',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'features' => 'array',
        'env_variables' => 'array',
        'payment_bumdes_enabled' => 'integer',
        'payment_winpay_enabled' => 'integer',
        'payment_tripay_enabled' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden.
     *
     * @var array
     */
    protected $hidden = [
        'whatsapp_token',
        'xendit_key',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when tenant is created/updated/deleted
        static::saved(function ($tenant) {
            Cache::forget('tenant:' . $tenant->domain);
            Cache::forget('tenant:rescode:' . $tenant->rescode);
        });

        static::deleted(function ($tenant) {
            Cache::forget('tenant:' . $tenant->domain);
            Cache::forget('tenant:rescode:' . $tenant->rescode);
        });
    }

    /**
     * Password mutator - store as plain text
     * Tidak di-encrypt karena column VARCHAR(191) terlalu pendek untuk encrypted string
     */
    public function setDbPasswordAttribute($value)
    {
        $this->attributes['db_password'] = $value;
    }

    /**
     * Password accessor - return as plain text
     */
    public function getDbPasswordAttribute($value)
    {
        return $value;
    }

    /**
     * Encrypt whatsapp token
     */
    public function setWhatsappTokenAttribute($value)
    {
        $this->attributes['whatsapp_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt whatsapp token
     */
    public function getWhatsappTokenAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Encrypt xendit key
     */
    public function setXenditKeyAttribute($value)
    {
        $this->attributes['xendit_key'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt xendit key
     */
    public function getXenditKeyAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Get tenant by domain with caching
     */
    public static function getByDomain($domain)
    {
        return Cache::remember('tenant:' . $domain, 3600, function () use ($domain) {
            return static::where('domain', $domain)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get tenant by rescode with caching
     */
    public static function getByRescode($rescode)
    {
        return Cache::remember('tenant:rescode:' . $rescode, 3600, function () use ($rescode) {
            return static::where('rescode', $rescode)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get all active tenants
     */
    public static function getAllActive()
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Convert to array for TenantMiddleware
     */
    public function toTenantArray()
    {
        $data = [
            'tenant_id' => $this->id,
            'domain' => $this->domain,
            'is_active' => (int) $this->is_active,
            'app_name' => $this->app_name,
            'signature' => $this->signature,
            'rescode' => $this->rescode,
            'db_host' => $this->db_host,
            'db_port' => $this->db_port,
            'db_database' => $this->db_database,
            'db_username' => $this->db_username,
            'db_password' => $this->db_password,
            'mail_from' => $this->mail_from,
            'whatsapp_token' => $this->whatsapp_token,
            'xendit_key' => $this->xendit_key,
            'features' => $this->features ?? [],
            'payment_bumdes_enabled' => $this->payment_bumdes_enabled ?? 1,
            'payment_winpay_enabled' => $this->payment_winpay_enabled ?? 1,
            'payment_tripay_enabled' => $this->payment_tripay_enabled ?? 1,
        ];

        // Merge custom env variables
        if ($this->env_variables && is_array($this->env_variables)) {
            $data = array_merge($data, $this->env_variables);
        }

        return $data;
    }
}
