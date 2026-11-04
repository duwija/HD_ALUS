<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Bukti transaksi akuntansi. Kolom path relatif terhadap storage/tenants/{RESCODE}/app/.
 */
class JurnalAttachment extends Model
{
    protected $table = 'jurnal_attachments';

    protected $fillable = ['code', 'original_name', 'path', 'mime', 'size', 'uploaded_by'];

    public const MIMES = 'jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt';
    public const MAX_KB = 10240; // 10 MB per file

    /** False bila migrasi jurnal_attachments belum dijalankan di DB tenant ini. */
    public static function available(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try {
                $ok = \Illuminate\Support\Facades\Schema::hasTable('jurnal_attachments');
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        return $ok;
    }

    /** Folder dasar lampiran milik tenant aktif. */
    public static function baseDir(): string
    {
        $rescode = preg_replace('/[^A-Za-z0-9_-]/', '', (string) (tenant_rescode() ?: 'default'));

        return storage_path('tenants/' . $rescode . '/app');
    }

    public function fullPath(): string
    {
        return self::baseDir() . '/' . ltrim($this->path, '/');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }
}
