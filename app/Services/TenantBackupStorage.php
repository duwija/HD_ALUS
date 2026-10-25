<?php

namespace App\Services;

/**
 * Lokasi file backup database tenant (dump .sql/.sql.gz).
 *
 * Dulu disimpan di public/tenants/{rescode}/backup — folder publik, sehingga dump database
 * bisa diunduh siapa saja lewat https://<domain>/tenants/{rescode}/backup/<file>. Sekarang di
 * storage tenant (tidak bisa diakses web): storage/tenants/{rescode}/app/backup, dan hanya bisa
 * diunduh lewat aplikasi (admin tenant /file/backup, atau superadmin).
 */
class TenantBackupStorage
{
    const RETENTION_DAYS = 7;

    public static function dir(string $rescode): string
    {
        $dir = storage_path('tenants/' . preg_replace('/[^A-Za-z0-9_-]/', '', $rescode) . '/app/backup');
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        return $dir;
    }

    /** Path file backup yang aman (tanpa path traversal), atau null bila tidak ada. */
    public static function file(string $rescode, string $filename): ?string
    {
        $path = self::dir($rescode) . '/' . basename($filename);

        return is_file($path) ? $path : null;
    }

    /** @return \SplFileInfo[] terbaru dulu */
    public static function files(string $rescode): array
    {
        $files = \Illuminate\Support\Facades\File::files(self::dir($rescode));
        usort($files, fn ($a, $b) => $b->getMTime() <=> $a->getMTime());

        return $files;
    }

    /** Hapus dump yang lebih tua dari RETENTION_DAYS di semua tenant. */
    public static function pruneAll(): int
    {
        $limit = time() - self::RETENTION_DAYS * 86400;
        $deleted = 0;
        foreach (glob(storage_path('tenants/*/app/backup/*')) ?: [] as $path) {
            if (is_file($path) && filemtime($path) < $limit && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
