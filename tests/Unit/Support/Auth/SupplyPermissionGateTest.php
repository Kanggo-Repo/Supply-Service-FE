<?php

namespace Tests\Unit\Support\Auth;

use App\Models\User;
use App\Support\Auth\SupplyPermissionGate;
use Tests\TestCase;

class SupplyPermissionGateTest extends TestCase
{
    public function test_platform_operator_role_is_treated_as_bootstrap_admin_for_supply_permissions(): void
    {
        $user = new User([
            'role_snapshot' => ['platform_operator'],
            'permission_snapshot' => ['users.manage'],
        ]);

        $gate = new SupplyPermissionGate;

        $this->assertTrue($gate->allows($user, 'materials.view'));
        $this->assertTrue($gate->allowsAny($user, ['materials.view', 'stores.view']));
    }

    public function test_super_admin_role_is_treated_as_bootstrap_admin_for_supply_permissions(): void
    {
        $user = new User([
            'role_snapshot' => ['super_admin'],
            'permission_snapshot' => ['users.manage'],
        ]);

        $gate = new SupplyPermissionGate;

        $this->assertTrue($gate->allows($user, 'units.delete'));
        $this->assertTrue($gate->allowsAny($user, ['store-search-radius.update']));
    }

    public function test_regular_user_with_empty_permission_snapshot_is_not_treated_as_full_access(): void
    {
        $user = new User([
            'role_snapshot' => ['purchasing'],
            'permission_snapshot' => [],
        ]);

        $gate = new SupplyPermissionGate;

        $this->assertFalse($gate->allows($user, 'materials.view'));
        $this->assertFalse($gate->allowsAny($user, ['materials.view', 'stores.view']));
    }
}
