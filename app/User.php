<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'full_name', 'email', 'password', 'date_of_bird', 'job_title', 'employee_type', 'join_date', 'address', 'phone','privilege','date_of_birth', 'email', 'photo','description', 'updated_at','created_at','deleted_at','id_merchant', 'dashboard_preference',
        'supervisor_id', 'employee_id', 'is_active_employee', 'is_active', 'fcm_token',
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
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
    ];

    public function user($id)
    {
     $user = $this->where('id', $id)
     ->first();
     return $user;

 }
 public function groups()
 {
    return $this->belongsToMany(Group::class, 'usergroups', 'id_user', 'id_group');
}
public function akuns()
{
    return $this->belongsToMany(Akun::class, 'akunusers', 'id_user', 'akun_code');
}

// ── Employee / Attendance Relations ────────────────────────────────────
public function supervisor()
{
    return $this->belongsTo(User::class, 'supervisor_id');
}

public function subordinates()
{
    return $this->hasMany(User::class, 'supervisor_id');
}

public function attendances()
{
    return $this->hasMany(Attendance::class);
}

public function shiftSchedules()
{
    return $this->hasMany(ShiftSchedule::class);
}

public function todayAttendance()
{
    return $this->hasOne(Attendance::class)->whereDate('date', today());
}
}
