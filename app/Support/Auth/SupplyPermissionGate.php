<?php

namespace App\Support\Auth;

use App\Models\User;

class SupplyPermissionGate
{
    public function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        $permissions = $this->permissions($user);
        if ($permissions === []) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * @return list<string>
     */
    public function permissions(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $permissions = $user->permission_snapshot;
        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $permissions,
        )));
    }

    /**
     * @return list<string>
     */
    public function roles(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $roles = $user->role_snapshot;
        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $roles,
        )));
    }
}
