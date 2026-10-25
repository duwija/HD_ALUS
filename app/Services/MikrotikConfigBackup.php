<?php

namespace App\Services;

use App\MikrotikBackup;
use Illuminate\Support\Facades\Crypt;

/**
 * Backup konfigurasi MikroTik lewat API (tanpa FTP/SSH):
 *  1. script sementara di router menjalankan `/export show-sensitive file=...`
 *     (/export tidak bisa dipanggil langsung lewat API),
 *  2. file dibaca utuh dengan /file/read (RouterOS >= 7.13) memakai RouterOsRawApi,
 *  3. script & file sementara dihapus dari router,
 *  4. isi disimpan terenkripsi (APP_KEY) di storage tenant masing-masing:
 *     storage/tenants/{rescode}/app/mikrotik-backups/{router}/ (file_path relatif ke storage/)
 *     — hanya bila konfigurasi berubah dibanding backup terakhir.
 *
 * Export memuat password (show-sensitive) supaya bisa di-restore utuh; karena itu file
 * selalu terenkripsi dan hanya bisa diunduh lewat aplikasi (admin/noc).
 */
class MikrotikConfigBackup
{
    const MIN_VERSION = '7.13';
    const RETENTION_DAYS = 7;
    const CHUNK = 32768;
    const MAX_BYTES = 50 * 1024 * 1024;

    /** Backup satu router di tenant yang sedang aktif. */
    public function backup($distrouter, int $tenantId, string $triggeredBy = 'schedule'): MikrotikBackup
    {
        $record = new MikrotikBackup([
            'tenant_id' => $tenantId,
            'distrouter_id' => $distrouter->id,
            'router_name' => $distrouter->name,
            'triggered_by' => $triggeredBy,
        ]);

        $api = null;
        $tmp = 'billing-backup-' . substr(bin2hex(random_bytes(4)), 0, 8);
        try {
            $api = new RouterOsRawApi((string) $distrouter->ip, (int) ($distrouter->port ?: 8728), (string) $distrouter->user, (string) $distrouter->password);

            $version = (string) ($api->command(['/system/resource/print'])[0]['version'] ?? '');
            $record->ros_version = $version;
            if (version_compare(strtok($version, ' '), self::MIN_VERSION, '<')) {
                $record->status = 'unsupported';
                $record->message = "RouterOS {$version}: isi file tidak bisa dibaca utuh lewat API. Upgrade ke >= " . self::MIN_VERSION . '.';
                $record->save();
                return $record;
            }

            $content = $this->export($api, $tmp);

            // Bandingkan tanpa baris pertama ("# <tanggal> by RouterOS ...") yang selalu berbeda.
            $hash = hash('sha256', preg_replace('/\A#[^\n]*\n/', '', $content));
            $last = MikrotikBackup::where('tenant_id', $tenantId)->where('distrouter_id', $distrouter->id)
                ->where('status', 'ok')->latest('id')->first();

            $record->size = strlen($content);
            $record->sha256 = $hash;
            if ($last && $last->sha256 === $hash && $last->file_path && is_file(storage_path($last->file_path))) {
                $record->status = 'unchanged';
                $record->message = 'Sama dengan backup ' . $last->created_at->format('d/m/Y H:i');
            } else {
                $rel = sprintf('%s/%d/%s.rsc.enc', self::tenantDir($tenantId), $distrouter->id, now()->format('Ymd_His'));
                $abs = storage_path($rel);
                if (!is_dir(dirname($abs))) {
                    mkdir(dirname($abs), 0750, true);
                }
                file_put_contents($abs, Crypt::encryptString($content));
                chmod($abs, 0640);
                $record->status = 'ok';
                $record->file_path = $rel;
                $record->message = substr_count($content, "\n") . ' baris';
            }
        } catch (\Throwable $e) {
            $record->status = 'failed';
            $record->message = mb_substr($e->getMessage(), 0, 500);
        } finally {
            if ($api) {
                $this->cleanup($api, $tmp);
            }
        }

        $record->save();
        return $record;
    }

    private function export(RouterOsRawApi $api, string $tmp): string
    {
        $api->command(['/system/script/add', '=name=' . $tmp, '=source=/export show-sensitive file=' . $tmp]);
        $api->command(['/system/script/run', '=number=' . $tmp]);

        // Tunggu file muncul dan ukurannya stabil.
        $file = $tmp . '.rsc';
        $size = -1;
        for ($i = 0; $i < 60; $i++) {
            sleep(1);
            $row = $api->command(['/file/print', '?name=' . $file])[0] ?? null;
            if ($row && isset($row['size']) && (int) $row['size'] > 0 && (int) $row['size'] === $size) {
                break;
            }
            $size = $row ? (int) ($row['size'] ?? 0) : -1;
        }
        if ($size <= 0) {
            throw new \RuntimeException('File export tidak terbentuk di router');
        }
        if ($size > self::MAX_BYTES) {
            throw new \RuntimeException("File export terlalu besar ({$size} byte)");
        }

        $data = '';
        while (strlen($data) < $size) {
            $part = $api->command(['/file/read', '=file=' . $file, '=offset=' . strlen($data), '=chunk-size=' . self::CHUNK])[0]['data'] ?? '';
            if ($part === '') {
                break;
            }
            $data .= $part;
        }
        if (strlen($data) !== $size) {
            throw new \RuntimeException('Export terbaca ' . strlen($data) . " dari {$size} byte");
        }

        return $this->stripTempScript($data, $tmp);
    }

    /**
     * Script sementara pembuat export ikut tercetak di bagian /system script. Buang entrinya
     * (beserta baris lanjutan "\") supaya tidak ikut ter-restore dan isi bisa dibandingkan antar backup.
     */
    private function stripTempScript(string $export, string $tmp): string
    {
        $lines = explode("\n", $export);
        $out = [];
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            if (strpos($lines[$i], 'add ') === 0) {
                $entry = [$lines[$i]];
                while (substr(rtrim($lines[$i], "\r"), -1) === '\\' && $i + 1 < $n) {
                    $entry[] = $lines[++$i];
                }
                if (strpos(implode("\n", $entry), 'name=' . $tmp) === false) {
                    array_push($out, ...$entry);
                }
                continue;
            }
            $out[] = $lines[$i];
        }

        // Header "/system script" yang tinggal kosong ikut dibuang.
        $text = implode("\n", $out);
        return preg_replace('#^/system script\r?\n(?=/|\z)#m', '', $text);
    }

    /** Hapus script & file sementara di router — dipanggil walau backup gagal. */
    private function cleanup(RouterOsRawApi $api, string $tmp): void
    {
        foreach ([['/system/script/remove', $tmp], ['/file/remove', $tmp . '.rsc']] as [$cmd, $name]) {
            try {
                $api->command([$cmd, '=numbers=' . $name]);
            } catch (\Throwable $e) {
                // tidak ada / sudah terhapus
            }
        }
    }

    /**
     * Folder backup di storage tenant (relatif ke storage/), sama dengan root disk "local"
     * tenant di TenantMiddleware: storage/tenants/{rescode}/app.
     */
    public static function tenantDir(int $tenantId): string
    {
        $rescode = \App\Tenant::on('isp_master')->whereKey($tenantId)->value('rescode') ?: (string) $tenantId;

        return 'tenants/' . preg_replace('/[^A-Za-z0-9_-]/', '', $rescode) . '/app/mikrotik-backups';
    }

    /** Isi backup (plaintext) untuk diunduh. */
    public static function content(MikrotikBackup $backup): string
    {
        return Crypt::decryptString(file_get_contents(storage_path($backup->file_path)));
    }

    /**
     * Hapus backup lebih tua dari RETENTION_DAYS — kecuali backup 'ok' terbaru tiap router
     * (agar router yang konfigurasinya lama tidak berubah tetap punya satu salinan).
     */
    public static function prune(): int
    {
        $keep = MikrotikBackup::where('status', 'ok')
            ->selectRaw('MAX(id) AS id')->groupBy('tenant_id', 'distrouter_id')->pluck('id');

        $old = MikrotikBackup::where('created_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->whereNotIn('id', $keep)->get();
        foreach ($old as $b) {
            if ($b->file_path) {
                @unlink(storage_path($b->file_path));
            }
            $b->delete();
        }

        return $old->count();
    }
}
