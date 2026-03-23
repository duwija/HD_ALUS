<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Model PaymentGateway
 *
 * Menyimpan konfigurasi payment gateway per tenant.
 * Setiap baris = satu provider untuk satu tenant.
 *
 * Kolom penting:
 *  - domain       : identifikasi tenant (FK logis ke tenants.domain)
 *  - provider     : slug unik provider (xendit, winpay, tripay, bumdes, ...)
 *  - enabled      : 0/1
 *  - fee_type     : none | fixed | percent
 *  - fee_amount   : nominal rupiah (fixed) atau angka persen (percent, misal 2.5 = 2.5%)
 *  - settings     : JSON untuk config khusus provider (channel list, API key override, dll)
 */
class PaymentGateway extends Model
{
    protected $table = 'payment_gateways';

    protected $fillable = [
        'domain',
        'provider',
        'label',
        'icon',
        'enabled',
        'fee_type',
        'fee_amount',
        'fee_label',
        'sort_order',
        'settings',
    ];

    protected $casts = [
        'enabled'    => 'boolean',
        'fee_amount' => 'float',
        'settings'   => 'array',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Hanya gateway yang aktif, urut sesuai sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('enabled', 1)->orderBy('sort_order');
    }

    /**
     * Filter per domain (tenant).
     */
    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Ambil semua gateway aktif untuk tenant saat ini.
     * Karena setiap tenant punya DB sendiri, tidak perlu filter domain.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function activeForCurrentTenant(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return static::active()->get();
        } catch (\Exception $e) {
            // Tabel belum ada di tenant ini — kembalikan koleksi kosong
            return new \Illuminate\Database\Eloquent\Collection();
        }
    }

    /**
     * Ambil satu gateway berdasarkan provider slug.
     */
    public static function findForCurrentTenant(string $provider): ?self
    {
        try {
            return static::where('provider', $provider)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Hitung biaya gateway untuk jumlah tagihan tertentu.
     *
     * @param  float $amount  Jumlah tagihan asli
     * @return float          Biaya tambahan (0 jika fee_type = none)
     */
    public function calculateFee(float $amount): float
    {
        return match ($this->fee_type) {
            'fixed'   => (float) $this->fee_amount,
            'percent' => round($amount * $this->fee_amount / 100),
            default   => 0.0,
        };
    }

    /**
     * Jumlah total yang harus dibayar customer termasuk biaya gateway.
     *
     * @param  float $amount  Jumlah tagihan asli
     * @return float
     */
    public function totalAmount(float $amount): float
    {
        return $amount + $this->calculateFee($amount);
    }

    /**
     * Format deskripsi biaya untuk ditampilkan.
     * Contoh: "Biaya Admin Rp 2.500" atau "Biaya Admin 2,5%"
     */
    public function feeDescription(): string
    {
        if ($this->fee_type === 'none' || $this->fee_amount <= 0) {
            return 'Gratis';
        }
        if ($this->fee_type === 'fixed') {
            return $this->fee_label . ' Rp ' . number_format($this->fee_amount, 0, ',', '.');
        }
        return $this->fee_label . ' ' . number_format($this->fee_amount, 2, ',', '.') . '%';
    }

    // ── Default data per provider ─────────────────────────────────────────────

    /**
     * Daftar default provider — digunakan oleh seeder.
     * Tambah provider baru di sini tanpa perlu migration.
     */
    public static function defaultProviders(): array
    {
        return [
            [
                'provider'   => 'xendit',
                'label'      => 'Xendit',
                'icon'       => 'fas fa-bolt',
                'sort_order' => 1,
                'settings'   => ['subtitle' => 'VA, E-Wallet, QRIS'],
            ],
            [
                'provider'   => 'winpay',
                'label'      => 'Winpay',
                'icon'       => 'fas fa-building-columns',
                'sort_order' => 2,
                'settings'   => ['subtitle' => 'Bank VA & Retail Outlet'],
            ],
            [
                'provider'   => 'tripay',
                'label'      => 'Tripay',
                'icon'       => 'fas fa-credit-card',
                'sort_order' => 3,
                'settings'   => ['subtitle' => 'E-Wallet & QRIS'],
            ],
            [
                'provider'   => 'bumdes',
                'label'      => 'Bumdes / Payment Point',
                'icon'       => 'fas fa-store',
                'sort_order' => 4,
                'settings'   => ['subtitle' => 'Bayar di Loket Terdekat'],
            ],

            // ── Tambah provider baru di bawah ini ────────────────────────────
            // Cukup tambah array baru, lalu jalankan:
            //   php artisan db:seed --class="\\PaymentGatewaySeeder"
            // View akan otomatis menampilkan kartu provider baru.
            //
            // Contoh: Duitku
            [
                'provider'   => 'duitku',
                'label'      => 'Duitku',
                'icon'       => 'fas fa-wallet',
                'sort_order' => 5,
                'enabled'    => 0,   // default off — aktifkan per tenant via SQL atau admin panel
                'settings'   => [
                    'subtitle'     => 'VA, QRIS & E-Wallet',
                    'merchant_code'=> '',   // diisi per-tenant lewat kolom settings di DB
                    'api_key'      => '',
                ],
            ],
            // ─────────────────────────────────────────────────────────────────
        ];
    }
}
