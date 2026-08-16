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
 * Enkripsi olts.password di semua tenant, dengan checkpoint (backup table)
 * per tenant supaya bisa di-rollback kalau ada yang salah.
 *
 * Sama persis pola & urutan amannya dengan distrouter:encrypt-passwords — jalankan
 * command ini SEBELUM menambahkan cast 'encrypted' di app/Olt.php, supaya tidak ada
 * jendela waktu di mana data masih plaintext tapi cast sudah coba decrypt (DecryptException).
 */
class EncryptOltPasswords extends Command
{
    protected $signature   = 'olt:encrypt-passwords
                            {--tenant= : Filter by tenant domain (optional)}
                            {--rollback : Kembalikan password ke plaintext asli dari backup, lalu berhenti}
                            {--dry-run : Simulasikan saja, tidak ada perubahan data}';
    protected $description = 'Enkripsi olts.password at rest, dengan backup table per tenant untuk rollback';

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

            if (!Schema::hasTable('olts')) {
                $this->line('  (tidak ada tabel olts)');
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
        // olts.password aslinya varchar(50) — hasil Crypt::encryptString() bisa ~200+ karakter
        // (base64 dari JSON iv/mac/value). Perbesar dulu supaya tidak truncate/error saat UPDATE.
        // Idempotent: MODIFY ke definisi yang sama tidak masalah kalau di-run ulang.
        $column = DB::selectOne("SHOW COLUMNS FROM olts WHERE Field = 'password'");
        if ($column && !$dryRun && !str_contains(strtolower($column->Type), 'varchar(255)') && !str_contains(strtolower($column->Type), 'text')) {
            DB::statement('ALTER TABLE olts MODIFY password VARCHAR(255) NOT NULL');
        }

        // Checkpoint table — baris lama TIDAK pernah ditimpa (insertOrIgnore), supaya backup
        // selalu menyimpan plaintext ASLI meskipun command ini di-run berkali-kali.
        // VARCHAR(50) di backup table cukup — ini nyimpen plaintext asli, bukan ciphertext.
        DB::statement("CREATE TABLE IF NOT EXISTS olts_password_backup (
            id BIGINT UNSIGNED PRIMARY KEY,
            password VARCHAR(50) NOT NULL,
            backed_up_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $rows = DB::table('olts')->select('id', 'name', 'password')->get();

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
            $inserted = DB::table('olts_password_backup')->insertOrIgnore([
                'id'       => $row->id,
                'password' => $row->password,
            ]);
            if ($inserted) {
                $backed++;
            }

            // 2) Enkripsi & simpan.
            $cipher = Crypt::encryptString($row->password);
            DB::table('olts')->where('id', $row->id)->update(['password' => $cipher]);

            // 3) Verifikasi round-trip — kalau gagal, hentikan seluruh proses untuk tenant ini.
            $stored = DB::table('olts')->where('id', $row->id)->value('password');
            if (Crypt::decryptString($stored) !== $row->password) {
                throw new \RuntimeException("Verifikasi round-trip gagal untuk olt id={$row->id} ({$row->name})");
            }

            $encrypted++;
        }

        $suffix = $dryRun ? ' (dry-run, tidak ada perubahan)' : ", di-backup: {$backed}";
        $this->line("  Sudah terenkripsi sebelumnya: {$alreadyDone}, baru dienkripsi: {$encrypted}{$suffix}");
    }

    protected function rollbackTenant(bool $dryRun)
    {
        if (!Schema::hasTable('olts_password_backup')) {
            $this->line('  Tidak ada backup untuk tenant ini — tidak ada yang di-rollback.');
            return;
        }

        $backups = DB::table('olts_password_backup')->get();

        if ($backups->isEmpty()) {
            $this->line('  Backup table kosong.');
            return;
        }

        $restored = 0;
        foreach ($backups as $b) {
            if (!$dryRun) {
                DB::table('olts')->where('id', $b->id)->update(['password' => $b->password]);
            }
            $restored++;
        }

        $suffix = $dryRun ? ' (dry-run, tidak ada perubahan)' : '.';
        $this->line("  Rollback: {$restored} baris dikembalikan ke plaintext asli{$suffix}");
    }
}
