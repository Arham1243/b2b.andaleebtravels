<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class B2bAdminRole extends Model
{
    protected $table = 'b2b_admin_roles';

    protected $fillable = [
        'name',
        'slug',
        'is_super',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_super' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(B2bAdmin::class, 'admin_role_id');
    }

    public function permissionList(): array
    {
        $permissions = $this->permissions;

        return is_array($permissions)
            ? array_values(array_filter($permissions, fn ($value) => is_string($value) && $value !== ''))
            : [];
    }
}
