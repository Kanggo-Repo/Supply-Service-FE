<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonolithAuthBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.monolith_auth.enabled', true);
        config()->set('services.monolith_auth.base_url', 'https://staging-material.ekagalang.my.id');
        config()->set('services.monolith_auth.handoff_start_path', '/auth/handoff/start');
        config()->set('services.monolith_auth.handoff_redeem_path', '/api/internal/auth/handoffs/redeem');
        config()->set('services.monolith_auth.handoff_logout_path', '/auth/handoff/logout');
        config()->set('services.monolith_auth.verify_ssl', false);
    }

    public function test_guest_is_redirected_from_materials_route_to_monolith_bridge(): void
    {
        $response = $this->get('/materials');

        $response->assertRedirect(route('auth.redirect'));
    }

    public function test_login_page_uses_supply_bridge_copy(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Portal Login Database Supply dan Jaringan Toko.');
        $response->assertSee('Masuk dengan Akun Monolith');
    }

    public function test_auth_redirect_points_to_monolith_handoff_start_with_fe_consumer_url(): void
    {
        $response = $this->get(route('auth.redirect'));

        $expected = 'https://staging-material.ekagalang.my.id/auth/handoff/start?'
            .http_build_query(['return_to' => route('auth.consume')], '', '&', PHP_QUERY_RFC3986);

        $response->assertRedirect($expected);
    }

    public function test_consume_redeems_monolith_handoff_and_creates_local_session_shell_user(): void
    {
        Http::fake([
            'https://staging-material.ekagalang.my.id/api/internal/auth/handoffs/redeem' => Http::response([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => 77,
                        'name' => 'Supply Bridge User',
                        'email' => 'bridge@example.com',
                        'roles' => ['supply_admin'],
                        'permissions' => ['materials.view', 'stores.view', 'units.view'],
                    ],
                    'return_to' => route('auth.consume'),
                ],
            ], 200),
        ]);

        $response = $this->get('/auth/consume?handoff_token=test-token');

        $response->assertRedirect(route('materials.index'));
        $this->assertAuthenticated();

        $user = \App\Models\User::query()->where('email', 'bridge@example.com')->firstOrFail();

        $this->assertSame('monolith', $user->auth_provider);
        $this->assertSame('monolith:77', $user->auth_subject);
        $this->assertSame(['supply_admin'], $user->role_snapshot);
        $this->assertSame(['materials.view', 'stores.view', 'units.view'], $user->permission_snapshot);
        $this->assertNotNull($user->last_login_at);
    }
}
