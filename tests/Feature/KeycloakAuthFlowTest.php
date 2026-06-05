<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KeycloakAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'services.keycloak.base_url' => 'https://auth.example.test',
            'services.keycloak.realm' => 'kanggo',
            'services.keycloak.client_id' => 'supply-fe',
            'services.platform_service.base_url' => 'http://127.0.0.1:8020',
            'services.calculation_fe.base_url' => 'http://calcfe.lvh.me:8001',
            'services.platform_fe.base_url' => 'http://platformfe.lvh.me:8021',
        ]);
    }

    public function test_guest_is_redirected_from_materials_route_to_keycloak_entrypoint(): void
    {
        $response = $this->get('/materials');

        $response->assertRedirect(route('auth.redirect'));
    }

    public function test_login_route_redirects_directly_to_keycloak_authorize_endpoint(): void
    {
        $response = $this->get(route('login'));

        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');

        $this->assertStringContainsString(
            'https://auth.example.test/realms/kanggo/protocol/openid-connect/auth',
            $redirectUrl,
        );
        $this->assertStringContainsString('client_id=supply-fe', $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);
    }

    public function test_auth_redirect_points_to_keycloak_authorize_endpoint(): void
    {
        $response = $this->get(route('auth.redirect'));

        $response->assertRedirect();

        $redirectUrl = $response->headers->get('Location');

        $this->assertStringContainsString(
            'https://auth.example.test/realms/kanggo/protocol/openid-connect/auth',
            $redirectUrl,
        );
        $this->assertStringContainsString('client_id=supply-fe', $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);
        $this->assertTrue(session()->has('oidc_state'));
        $this->assertTrue(session()->has('oidc_code_verifier'));
    }

    public function test_consume_exchanges_oidc_code_and_creates_local_session_shell_user(): void
    {
        Http::fake([
            'https://auth.example.test/realms/kanggo/protocol/openid-connect/token' => Http::response(
                [
                    'access_token' => 'access-token-123',
                    'refresh_token' => 'refresh-token-123',
                    'id_token' => 'id-token-123',
                    'expires_in' => 300,
                ],
            ),
            'http://127.0.0.1:8020/api/v1/me' => Http::response([
                'data' => [
                    'identity' => [
                        'subject' => 'kc-user-1',
                        'email' => 'bridge@example.com',
                        'name' => 'Supply Bridge User',
                    ],
                    'roles' => ['supply_admin'],
                    'permissions' => [
                        'materials.view',
                        'stores.view',
                        'units.view',
                    ],
                ],
            ]),
            'http://127.0.0.1:8020/api/v1/navigation' => Http::response([
                'data' => [
                    'preferred_app' => 'supply',
                    'pending_access' => false,
                    'allowed_services' => ['supply'],
                    'blocked_services' => [],
                    'pending_services' => ['platform', 'calculation'],
                ],
            ]),
        ]);

        $response = $this->withSession([
            'oidc_state' => 'expected-state',
            'oidc_code_verifier' => 'verifier-123',
        ])->get('/auth/consume?code=authorization-code&state=expected-state');

        $response->assertRedirect(route('materials.index'));
        $response->assertCookie('kanggo_active_subject', 'keycloak:kc-user-1');
        $this->assertAuthenticated();

        $user = User::query()
            ->where('email', 'bridge@example.com')
            ->firstOrFail();

        $this->assertSame('keycloak', $user->auth_provider);
        $this->assertSame('keycloak:kc-user-1', $user->auth_subject);
        $this->assertSame(['supply_admin'], $user->role_snapshot);
        $this->assertSame(
            ['materials.view', 'stores.view', 'units.view'],
            $user->permission_snapshot,
        );
        $this->assertNotNull($user->last_login_at);
        $this->assertSame(['supply'], session('platform_allowed_services'));
    }

    public function test_consume_returns_user_to_remembered_page_after_standby_relogin(): void
    {
        Http::fake([
            'https://auth.example.test/realms/kanggo/protocol/openid-connect/token' => Http::response(
                [
                    'access_token' => 'access-token-123',
                    'refresh_token' => 'refresh-token-123',
                    'id_token' => 'id-token-123',
                    'expires_in' => 300,
                ],
            ),
            'http://127.0.0.1:8020/api/v1/me' => Http::response([
                'data' => [
                    'identity' => [
                        'subject' => 'kc-user-9',
                        'email' => 'bridge@example.com',
                        'name' => 'Supply Bridge User',
                    ],
                    'roles' => ['supply_admin'],
                    'permissions' => [
                        'materials.view',
                        'stores.view',
                        'units.view',
                    ],
                ],
            ]),
            'http://127.0.0.1:8020/api/v1/navigation' => Http::response([
                'data' => [
                    'preferred_app' => 'supply',
                    'pending_access' => false,
                    'allowed_services' => ['supply'],
                    'blocked_services' => [],
                    'pending_services' => ['platform', 'calculation'],
                ],
            ]),
        ]);

        $response = $this->withSession([
            'oidc_state' => 'expected-state',
            'oidc_code_verifier' => 'verifier-123',
            'auth_return_to' => '/stores?search=cement',
        ])->get('/auth/consume?code=authorization-code&state=expected-state');

        $response->assertRedirect('/stores?search=cement');
    }

    public function test_callback_alias_exchanges_oidc_code_and_creates_local_session_shell_user(): void
    {
        Http::fake([
            'https://auth.example.test/realms/kanggo/protocol/openid-connect/token' => Http::response(
                [
                    'access_token' => 'access-token-123',
                    'refresh_token' => 'refresh-token-123',
                    'id_token' => 'id-token-123',
                    'expires_in' => 300,
                ],
            ),
            'http://127.0.0.1:8020/api/v1/me' => Http::response([
                'data' => [
                    'identity' => [
                        'subject' => 'kc-user-callback',
                        'email' => 'callback@example.com',
                        'name' => 'Supply Callback User',
                    ],
                    'roles' => ['supply_admin'],
                    'permissions' => ['materials.view'],
                ],
            ]),
            'http://127.0.0.1:8020/api/v1/navigation' => Http::response([
                'data' => [
                    'preferred_app' => 'supply',
                    'pending_access' => false,
                    'allowed_services' => ['supply'],
                    'blocked_services' => [],
                    'pending_services' => ['platform', 'calculation'],
                ],
            ]),
        ]);

        $response = $this->withSession([
            'oidc_state' => 'expected-state',
            'oidc_code_verifier' => 'verifier-123',
        ])->get('/auth/callback?code=authorization-code&state=expected-state');

        $response->assertRedirect(route('materials.index'));
        $this->assertAuthenticated();
    }

    public function test_consume_redirects_to_calculation_service_when_user_has_no_supply_access(): void
    {
        Http::fake([
            'https://auth.example.test/realms/kanggo/protocol/openid-connect/token' => Http::response(
                [
                    'access_token' => 'access-token-123',
                    'refresh_token' => 'refresh-token-123',
                    'id_token' => 'id-token-123',
                    'expires_in' => 300,
                ],
            ),
            'http://127.0.0.1:8020/api/v1/me' => Http::response([
                'data' => [
                    'identity' => [
                        'subject' => 'kc-user-2',
                        'email' => 'calc-only@example.com',
                        'name' => 'Calc Only User',
                    ],
                    'roles' => ['calculation_operator'],
                    'permissions' => [],
                ],
            ]),
            'http://127.0.0.1:8020/api/v1/navigation' => Http::response([
                'data' => [
                    'preferred_app' => 'calculation',
                    'pending_access' => false,
                    'allowed_services' => ['calculation'],
                    'blocked_services' => [],
                    'pending_services' => ['platform', 'supply'],
                ],
            ]),
        ]);

        $this->withSession([
            'oidc_state' => 'expected-state',
            'oidc_code_verifier' => 'verifier-123',
        ])
            ->get('/auth/consume?code=authorization-code&state=expected-state')
            ->assertRedirect(
                'http://calcfe.lvh.me:8001?access_notice=service-denied&requested_service=supply',
            );
    }

    public function test_service_access_middleware_redirects_blocked_user_with_access_denied_notice(): void
    {
        $user = User::factory()->create([
            'role_snapshot' => ['purchasing'],
            'permission_snapshot' => ['dashboard.view'],
        ]);

        $this->actingAs($user)
            ->withSession([
                'platform_allowed_services' => ['platform'],
                'platform_pending_access' => false,
                'platform_preferred_app' => 'platform',
            ])
            ->get('/materials')
            ->assertRedirect(
                'http://platformfe.lvh.me:8021?access_notice=service-denied&requested_service=supply',
            );
    }

    public function test_platform_auth_middleware_logs_out_local_user_when_shared_subject_cookie_points_to_another_account(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'keycloak',
            'auth_subject' => 'keycloak:kc-user-old',
        ]);

        $this->actingAs($user)
            ->withSession(['platform_access_token' => 'access-token-123'])
            ->withCookie('kanggo_active_subject', 'keycloak:kc-user-new')
            ->get('/materials')
            ->assertRedirect(route('auth.redirect'));

        $this->assertGuest();
    }

    public function test_profile_route_redirects_to_platform_profile_owner_when_configured(): void
    {
        $user = User::factory()->create([
            'auth_provider' => 'keycloak',
            'auth_subject' => 'keycloak:kc-user-1',
        ]);

        $this->actingAs($user)
            ->withSession([
                'platform_allowed_services' => ['supply'],
                'platform_pending_access' => false,
            ])
            ->get('/profile')
            ->assertRedirect('http://platformfe.lvh.me:8021/profile');
    }
}
