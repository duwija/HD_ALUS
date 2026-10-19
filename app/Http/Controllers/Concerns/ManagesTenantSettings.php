<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Helper untuk halaman pengaturan sisi admin tenant yang menulis ke isp_master.tenants.
 * Tenant selalu ditentukan dari domain yang sedang diakses (tenant()), bukan dari input form,
 * dan setiap penulisan hanya menyentuh key yang disebut — key lain tidak ikut berubah.
 */
trait ManagesTenantSettings
{
    protected function authorizeAdmin(): void
    {
        if (Auth::user()->privilege !== 'admin') {
            abort(403, 'Akses ditolak: hanya admin.');
        }
    }

    protected function currentTenantRow()
    {
        // Sengaja pakai tenant() (tanpa fallback config/env) agar tidak pernah salah baris tenant.
        $domain = tenant('domain');
        abort_if(empty($domain), 404, 'Tenant tidak dikenali.');

        $tenant = DB::connection('isp_master')->table('tenants')
            ->select('id', 'domain', 'features', 'env_variables')
            ->where('domain', $domain)
            ->first();
        abort_if(!$tenant, 404, 'Tenant tidak ditemukan di isp_master.');

        return $tenant;
    }

    protected function decodeJsonColumn($raw): array
    {
        $data = is_string($raw) ? json_decode($raw, true) : (array) $raw;

        return is_array($data) ? $data : [];
    }

    /** Baca ENV tenant dengan urutan yang sama seperti tenant_config(): lowercase dulu, lalu UPPERCASE. */
    protected function readTenantEnv($tenant, string $key, $default = null)
    {
        $env = $this->decodeJsonColumn($tenant->env_variables);

        return $env[strtolower($key)] ?? $env[strtoupper($key)] ?? $default;
    }

    /**
     * Tulis beberapa key ENV tenant sekaligus (nilai di-bind sebagai parameter).
     * Kalau key sudah ada dalam bentuk lowercase/UPPERCASE, yang ada itulah yang ditimpa
     * agar tenant_config() membaca nilai baru; key baru ditulis UPPERCASE.
     *
     * @param  array<string,string>  $values  ['NOTIF_DELAY_MIN' => '10', ...] — key harus dari daftar tetap di kode
     */
    protected function writeTenantEnv($tenant, array $values): void
    {
        $env = $this->decodeJsonColumn($tenant->env_variables);

        $expr = "COALESCE(NULLIF(env_variables, ''), '{}')";
        $bindings = [];
        foreach ($values as $key => $value) {
            $existing = array_values(array_intersect([strtolower($key), strtoupper($key)], array_keys($env)));
            foreach ($existing ?: [strtoupper($key)] as $k) {
                $expr = "JSON_SET({$expr}, '$.{$k}', ?)";
                $bindings[] = (string) $value;
            }
        }

        DB::connection('isp_master')->update(
            "UPDATE tenants SET env_variables = {$expr}, updated_at = ? WHERE id = ?",
            array_merge($bindings, [now(), $tenant->id])
        );
    }
}
