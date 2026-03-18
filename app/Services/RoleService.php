<?php

namespace App\Services;

use App\Domain\Identity\Models\Role;
use App\Models\User;

class RoleService
{
    public function assign(User $user, string $roleCode): void
    {
        $role = Role::query()->where('code', $roleCode)->firstOrFail();

        if (! $user->roles()->where('roles.id', $role->id)->exists()) {
            $user->roles()->attach($role->id);
        }
    }
}
