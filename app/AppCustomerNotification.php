<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppCustomerNotification extends Model
{
    protected $table = 'app_customer_notifications';

    protected $fillable = [
        'customer_id', 'title', 'body', 'type', 'open_url', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Simpan notifikasi ke riwayat (dipanggil bersamaan dengan FCM)
     */
    public static function record(int $customerId, string $title, string $body, string $type, string $openUrl = ''): self
    {
        return static::create([
            'customer_id' => $customerId,
            'title'       => $title,
            'body'        => $body,
            'type'        => $type,
            'open_url'    => $openUrl,
            'is_read'     => false,
        ]);
    }

    /**
     * Scope: 30 hari terakhir
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /**
     * Hitung notif belum dibaca untuk customer tertentu
     */
    public static function countUnread(int $customerId): int
    {
        return static::where('customer_id', $customerId)
            ->where('is_read', false)
            ->recent()
            ->count();
    }
}
