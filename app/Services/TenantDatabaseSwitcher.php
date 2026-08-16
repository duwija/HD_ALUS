<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Switch koneksi 'mysql' ke database tenant tertentu, dipakai dari
 * TenantMiddleware maupun job-job queue (EnableMikrotikJob, IsolirJob, dll).
 *
 * Sebelumnya logika Config::set()+DB::purge()+DB::reconnect() ini terduplikasi
 * di banyak file dan tidak pernah diverifikasi berhasil — pada worker queue
 * yang berumur panjang (numprocs=2, gonta-ganti puluhan tenant per hari),
 * query PERTAMA setelah switch kadang gagal "Access denied" walau kredensial
 * yang tersimpan sebenarnya benar (retry singkat berikutnya selalu berhasil).
 * switchTo() sekarang memverifikasi via query ringan dan retry beberapa kali
 * sebelum menyerah, supaya job tidak gagal total karena celah race sesaat ini.
 */
class TenantDatabaseSwitcher
{
    /**
     * Ambil data tenant (sudah ter-decrypt) dari isp_master berdasarkan domain.
     */
    public static function fetchTenantArray(string $domain): ?array
    {
        $tenantModel = \App\Tenant::on('isp_master')->where('domain', $domain)->first();

        return $tenantModel ? $tenantModel->toTenantArray() : null;
    }

    /**
     * Switch koneksi 'mysql' ke database tenant $tenant, verifikasi dengan query
     * ringan, retry sampai $maxAttempts kali kalau gagal. Return true kalau sukses.
     */
    public static function switchTo(array $tenant, int $maxAttempts = 3): bool
    {
        $domain = $tenant['domain'] ?? '(unknown)';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $dbConfig = [
                'host'     => $tenant['db_host']     ?? env('DB_HOST'),
                'port'     => $tenant['db_port']     ?? env('DB_PORT'),
                'database' => $tenant['db_database'] ?? env('DB_DATABASE'),
                'username' => $tenant['db_username'] ?? env('DB_USERNAME'),
                'password' => $tenant['db_password'] ?? env('DB_PASSWORD'),
            ];
            foreach ($dbConfig as $key => $value) {
                Config::set('database.connections.mysql.' . $key, $value);
            }

            DB::purge('mysql');
            DB::reconnect('mysql');

            try {
                DB::connection('mysql')->select('select 1');
                return true;
            } catch (\Exception $e) {
                Log::warning("[TENANT] TenantDatabaseSwitcher percobaan {$attempt}/{$maxAttempts} gagal untuk {$domain}: " . $e->getMessage());
                if ($attempt < $maxAttempts) {
                    usleep(200000); // 200ms sebelum retry
                }
            }
        }

        Log::error("[TENANT] TenantDatabaseSwitcher GAGAL PERMANEN untuk {$domain} setelah {$maxAttempts} percobaan.");
        return false;
    }
}
