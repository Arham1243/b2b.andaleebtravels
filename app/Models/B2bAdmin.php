<?php

namespace App\Models;

use App\Notifications\B2bAdminResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class B2bAdmin extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    use Notifiable;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'b2b_admins';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'admin_role_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'password' => 'hashed',
        ];
    }

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(B2bAdminRole::class, 'admin_role_id');
    }

    public function isPortalActive(): bool
    {
        return ($this->status ?? self::STATUS_ACTIVE) !== self::STATUS_INACTIVE;
    }

    public function isSuperAdmin(): bool
    {
        $role = $this->adminRole;
        if ($role && $role->is_super) {
            return true;
        }

        return ! $this->admin_role_id && ($this->role ?? 'admin') === 'admin';
    }

    public function hasPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $list = $this->adminRole?->permissionList() ?? [];

        return in_array($key, $list, true);
    }

    public function isFullAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function isStaff(): bool
    {
        return ! $this->isSuperAdmin();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new B2bAdminResetPasswordNotification($token));
    }
}
