<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MikrotikSyncFailure extends Model
{
    protected $table = 'mikrotik_sync_failures';

    protected $fillable = [
        'customer_id', 'customer_name', 'customer_cid',
        'action', 'pppoe',
        'id_distrouter', 'distrouter_ip',
        'error_message', 'attempts',
        'status', 'resolved_at', 'resolved_by', 'notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function distrouter()
    {
        return $this->belongsTo(Distrouter::class, 'id_distrouter')->withTrashed();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Catat kegagalan sinkronisasi MikroTik ke tabel.
     */
    public static function record(
        ?int    $customerId,
        string  $customerName,
        ?string $customerCid,
        string  $action,
        ?string $pppoe,
        ?int    $idDistrouter,
        ?string $distrouterIp,
        string  $errorMessage,
        int     $attempts = 1
    ): self {
        return static::create([
            'customer_id'    => $customerId,
            'customer_name'  => $customerName,
            'customer_cid'   => $customerCid,
            'action'         => $action,
            'pppoe'          => $pppoe,
            'id_distrouter'  => $idDistrouter,
            'distrouter_ip'  => $distrouterIp,
            'error_message'  => $errorMessage,
            'attempts'       => $attempts,
            'status'         => 'pending',
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'retrying']);
    }

    // ── Badge helper ─────────────────────────────────────────────────────────

    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending'   => '<span class="badge badge-danger">Pending</span>',
            'retrying'  => '<span class="badge badge-warning">Retrying</span>',
            'resolved'  => '<span class="badge badge-success">Resolved</span>',
            default     => '<span class="badge badge-secondary">' . $this->status . '</span>',
        };
    }
}
