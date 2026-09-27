<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
    use Notifiable;

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_SUPERVISOR = 'supervisor';

    protected $connection = 'admin';
    protected $table = 'admin_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'is_active', 'role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role !== self::ROLE_SUPERVISOR;
    }

    /**
     * IDs of tenants this admin user is allowed to access.
     * Only meaningful for supervisor accounts — super admins are unrestricted.
     */
    public function assignedTenantIds(): array
    {
        return \DB::connection('admin')
            ->table('admin_user_tenants')
            ->where('admin_user_id', $this->id)
            ->pluck('tenant_id')
            ->toArray();
    }

    public function assignedTenants()
    {
        return \App\Tenant::whereIn('id', $this->assignedTenantIds())
            ->orderBy('app_name')
            ->get();
    }

    public function syncAssignedTenants(array $tenantIds): void
    {
        \DB::connection('admin')->table('admin_user_tenants')
            ->where('admin_user_id', $this->id)
            ->delete();

        $rows = collect($tenantIds)->filter()->unique()->map(function ($tenantId) {
            return [
                'admin_user_id' => $this->id,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->all();

        if (!empty($rows)) {
            \DB::connection('admin')->table('admin_user_tenants')->insert($rows);
        }
    }
}
