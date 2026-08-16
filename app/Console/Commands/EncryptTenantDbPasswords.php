<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Enkripsi isp_master.tenants.db_password — password MySQL untuk SELURUH database
 * bisnis tiap tenant (bukan cuma kredensial 1 device seperti router/OLT).
 *
 * Beda dari distrouter:encrypt-passwords / olt:encrypt-passwords: ini cuma SATU
 * database (isp_master), tidak perlu loop-switch per tenant.
 *
 * Kolom aslinya varchar(191) — sudah pernah dicoba sebelumnya (lihat komentar lama
 * di app/Tenant.php) dan sengaja dibatalkan karena hasil Crypt::encryptString() bisa
 * ~200+ karakter, tidak cukup. Command ini otomatis memperbesar kolom dulu.
 */
class EncryptTenantDbPasswords extends Command
{
    protected $signature   = 'tenant:encrypt-db-passwords
                            {--rollback : Kembalikan db_password ke plaintext asli dari backup, lalu berhenti}
                            {--dry-run : Simulasikan saja, tidak ada perubahan data}';
    protected $description = 'Enkripsi isp_master.tenants.db_password at rest, dengan backup table untuk rollback';

    public function handle()
    {
        $rollback = (bool) $this->option('rollback');
        $dryRun   = (bool) $this->option('dry-run');

        $db = DB::connection('isp_master');

        if (!$db->getSchemaBuilder()->hasTable('tenants')) {
            $this->error('Tabel tenants tidak ditemukan di koneksi isp_master.');
            return 1;
        }

        if ($rollback) {
            $this->rollback($db, $dryRun);
        } else {
            $this->encrypt($db, $dryRun);
        }

        $this->info('Done.');
        return 0;
    }

    protected function encrypt($db, bool $dryRun)
    {
        // Kolom aslinya varchar(191) — perbesar dulu, idempotent kalau di-run ulang.
        $column = $db->selectOne("SHOW COLUMNS FROM tenants WHERE Field = 'db_password'");
        if ($column && !$dryRun && !str_contains(strtolower($column->Type), 'varchar(255)') && !str_contains(strtolower($column->Type), 'text')) {
            $db->statement('ALTER TABLE tenants MODIFY db_password VARCHAR(255) NOT NULL');
            $this->info('Kolom db_password diperbesar ke VARCHAR(255).');
        }

        // Checkpoint table — baris lama TIDAK pernah ditimpa (insertOrIgnore).
        $db->statement("CREATE TABLE IF NOT EXISTS tenants_db_password_backup (
            id BIGINT UNSIGNED PRIMARY KEY,
            domain VARCHAR(191) NOT NULL,
            db_password VARCHAR(191) NOT NULL,
            backed_up_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $rows = $db->table('tenants')->select('id', 'domain', 'db_password')->get();

        $alreadyDone = 0;
        $encrypted   = 0;
        $backed      = 0;

        foreach ($rows as $row) {
            try {
                Crypt::decryptString($row->db_password);
                $alreadyDone++;
                continue;
            } catch (DecryptException $e) {
                // Masih plaintext.
            }

            $this->line("  {$row->domain} ...");

            if ($dryRun) {
                $encrypted++;
                continue;
            }

            $inserted = $db->table('tenants_db_password_backup')->insertOrIgnore([
                'id'          => $row->id,
                'domain'      => $row->domain,
                'db_password' => $row->db_password,
            ]);
            if ($inserted) {
                $backed++;
            }

            $cipher = Crypt::encryptString($row->db_password);
            $db->table('tenants')->where('id', $row->id)->update(['db_password' => $cipher]);

            $stored = $db->table('tenants')->where('id', $row->id)->value('db_password');
            if (Crypt::decryptString($stored) !== $row->db_password) {
                throw new \RuntimeException("Verifikasi round-trip gagal untuk tenant id={$row->id} ({$row->domain}) — BERHENTI.");
            }

            $encrypted++;
        }

        $suffix = $dryRun ? ' (dry-run, tidak ada perubahan)' : ", di-backup: {$backed}";
        $this->info("Sudah terenkripsi sebelumnya: {$alreadyDone}, baru dienkripsi: {$encrypted}{$suffix}");
    }

    protected function rollback($db, bool $dryRun)
    {
        if (!$db->getSchemaBuilder()->hasTable('tenants_db_password_backup')) {
            $this->line('Tidak ada backup — tidak ada yang di-rollback.');
            return;
        }

        $backups = $db->table('tenants_db_password_backup')->get();

        if ($backups->isEmpty()) {
            $this->line('Backup table kosong.');
            return;
        }

        $restored = 0;
        foreach ($backups as $b) {
            $this->line("  {$b->domain} ...");
            if (!$dryRun) {
                $db->table('tenants')->where('id', $b->id)->update(['db_password' => $b->db_password]);
            }
            $restored++;
        }

        $suffix = $dryRun ? ' (dry-run, tidak ada perubahan)' : '.';
        $this->info("Rollback: {$restored} baris dikembalikan ke plaintext asli{$suffix}");
    }
}
