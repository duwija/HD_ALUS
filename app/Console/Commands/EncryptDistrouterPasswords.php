<?php

namespace App\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enkripsi distrouters.password di semua tenant, dengan checkpoint (backup table)
 * per tenant supaya bisa di-rollback kalau ada yang salah.
 *
 * Urutan aman: jalankan command ini SEBELUM menambahkan cast 'encrypted' di
 * app/Distrouter.php. Kalau cast sudah aktif duluan, semua Eloquent read akan
 * coba decrypt data yang masih plaintext dan gagal (DecryptException) — job
 * seperti pppoe:sync-sessions-all / EnableMikrotikJob bisa langsung berhenti
 * connect ke router.
 */
class EncryptDistrouterPasswords extends Command
{
    protected $signature   = 'distrouter:encrypt-passwords
                            {--tenant= : Filter by tenant domain (optional)}
                            {--rollback : Kembalikan password ke plaintext asli dari backup, lalu berhenti}
                            {--dry-run : Simulasikan saja, tidak ada perubahan data}';
    protected $description = 'Enkripsi distrouters.password at rest, dengan backup table per tenant untuk rollback';

    public function handle()
    {
        $rollback = (bool) $this->option('rollback');
        $dryRun   = (bool) $this->option('dry-run');

        $tenants = Tenant::on('isp_master')
            ->where('is_active', true)
            ->when($this->option('tenant'), function ($q, $domain) {
                $q->where('domain', $domain);
            })
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return 0;
        }

        foreach ($tenants as $tenant) {
            if (empty($tenant->db_database)) {
                continue;
            }

            $this->info("=== Tenant: {$tenant->domain} | DB: {$tenant->db_database} ===");

            try {
                $this->switchDatabase($tenant);
            } catch (\Exception $e) {
                $this->error('  Cannot connect to DB: ' . $e->getMessage());
                $this->line('');
                continue;
            }

            if (!Schema::hasTable('distrouters')) {
                $this->line('  (tidak ada tabel distrouters)');
                $this->line('');
                continue;
            }

            try {
                if ($rollback) {
                    $this->rollbackTenant($dryRun);
                } else {
                    $this->encryptTenant($dryRun);
                }
            } catch (\Throwable $e) {
                $this->error('  BERHENTI: ' . $e->getMessage());
                $this->error('  Data tenant ini mungkin sebagian ter-encrypt. Jalankan --rollback untuk tenant ini sebelum retry.');
            }

            $this->line('');
        }

        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->info('Done.');
        return 0;
    }

    protected function switchDatabase(Tenant $tenant)
    {
        $dbUser = $tenant->db_username ?: env('DB_USERNAME');
        $dbPass = $tenant->db_password ?: env('DB_PASSWORD');

        Config::set('database.connections.mysql.host',     $tenant->db_host     ?: env('DB_HOST'));
        Config::set('database.connections.mysql.port',     $tenant->db_port     ?: env('DB_PORT'));
        Config::set('database.connections.mysql.database', $tenant->db_database);
        Config::set('database.connections.mysql.username', $dbUser);
        Config::set('database.connections.mysql.password', $dbPass);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    protected function encryptTenant(bool $dryRun)
    {
        // Checkpoint table — dibuat sekali, baris lama TIDAK pernah ditimpa (insertOrIgnore),
        // supaya backup selalu menyimpan plaintext ASLI meskipun command ini di-run berkali-kali.
        DB::statement("CREATE TABLE IF NOT EXISTS distrouters_password_backup (
            id BIGINT UNSIGNED PRIMARY KEY,
            password VARCHAR(255) NOT NULL,
            backed_up_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $rows = DB::table('distrouters')->select('id', 'name', 'password')->get();

        $alreadyDone = 0;
        $encrypted   = 0;
        $backed      = 0;

        foreach ($rows as $row) {
            // Kalau decrypt sukses, berarti baris ini sudah dienkripsi di run sebelumnya — skip.
            try {
                Crypt::decryptString($row->password);
                $alreadyDone++;
                continue;
            } catch (DecryptException $e) {
                // Masih plaintext, lanjut proses di bawah.
            }

            if ($dryRun) {
                $encrypted++;
                continue;
            }

            // 1) Checkpoint DULU sebelum data diubah.
            $inserted = DB::table('distrouters_password_backup')->insertOrIgnore([
                'id'       => $row->id,
                'password' => $row->password,
            ]);
            if ($inserted) {
                $backed++;
            }

            // 2) Enkripsi & simpan.
            $cipher = Crypt::encryptString($row->password);
            DB::table('distrouters')->where('id', $row->id)->update(['password' => $cipher]);

            // 3) Verifikasi round-trip — kalau gagal, hentikan seluruh proses untuk tenant ini.
            $stored = DB::table('distrouters')->where('id', $row->id)->value('password');
            if (Crypt::decryptString($stored) !== $row->password) {
                throw new \RuntimeException("Verifikasi round-trip gagal untuk distrouter id={$row->id} ({$row->name})");
            }

            $encrypted++;
        }

        $suffix = $dryRun ? ' (dry-run, tidak ada perubahan)' : ", di-backup: {$backed}";
        $this->line("  Sudah terenkripsi sebelumnya: {$alreadyDone}, baru dienkripsi: {$encrypted}{$suffix}");
    }

    protected function rollbackTenant(bool $dryRun)
    {
        if (!Schema::hasTable('distrouters_password_backup')) {
            $this->line('  Tidak ada backup untuk tenant ini — tidak ada yang di-rollback.');
            return;
        }

        $backups = DB::table('distrouters_password_backup')->get();

        if ($backups->isEmpty()) {
            $this->line('  Backup table kosong.');
            return;
        }

        $restored = 0;
        foreach ($backups as $b) {
            if (!$dryRun) {
                DB::table('distrouters')->where('id', $b->id)->update(['password' => $b->password]);
            }
            $restored++;
        }

        $suffix = $dryRun ? ' (dry-run, tidak ada perubahan)' : '.';
        $this->line("  Rollback: {$restored} baris dikembalikan ke plaintext asli{$suffix}");
    }
}
