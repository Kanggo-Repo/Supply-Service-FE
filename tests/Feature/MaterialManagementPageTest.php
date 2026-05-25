<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaterialManagementPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.monolith_auth.enabled', true);
        config()->set('services.supply_service.base_url', 'http://supply-be.test');
        config()->set('services.supply_service.service_name', 'supply-fe');
        config()->set('services.supply_service.token', 'local-supply-token');
        config()->set('services.supply_service.verify_ssl', false);
    }

    public function test_materials_index_reads_selected_family_and_filter_metadata_from_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'family' => 'brick',
                        'label' => 'Brick Alpha Roster',
                        'brand' => 'Alpha Brick',
                        'type' => 'Roster',
                        'price_per_piece' => 1200,
                        'store' => 'TB Alpha',
                    ],
                ],
                'current_page' => 1,
                'per_page' => 15,
                'total' => 1,
                'last_page' => 1,
            ], 200),
            'http://supply-be.test/api/v1/units/grouped' => Http::response([
                'success' => true,
                'data' => [
                    'brick' => [],
                    'cement' => [],
                    'nat' => [],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'steel' => [],
                    'kasa_gypsum' => [],
                    'paku_tembak' => [],
                    'paku' => [],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/materials?tab=brick&search=Alpha');

        $response->assertOk();
        $response->assertSee('Database Material');
        $response->assertSee('Alpha Brick');
        $response->assertSee('Roster');
    }

    public function test_materials_index_uses_all_family_totals_for_tab_badges_and_topbar(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake(function (ClientRequest $request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if ($path === '/api/v1/units/grouped') {
                return Http::response([
                    'success' => true,
                    'data' => [
                        'brick' => [],
                        'cement' => [],
                        'nat' => [],
                        'sand' => [],
                        'cat' => [],
                        'ceramic' => [],
                        'steel' => [],
                        'kasa_gypsum' => [],
                        'paku_tembak' => [],
                        'paku' => [],
                    ],
                ], 200);
            }

            if ($path === '/api/v1/materials/brick') {
                return Http::response([
                    'data' => [
                        [
                            'id' => 1,
                            'family' => 'brick',
                            'label' => 'Brick Alpha',
                            'brand' => 'Brick Alpha',
                            'type' => 'Roster',
                        ],
                    ],
                    'current_page' => 1,
                    'per_page' => 1,
                    'total' => 9,
                    'last_page' => 9,
                ], 200);
            }

            if ($path === '/api/v1/materials/cement') {
                return Http::response([
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 1,
                    'total' => 165,
                    'last_page' => 165,
                ], 200);
            }

            if ($path === '/api/v1/materials/nat') {
                return Http::response([
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 1,
                    'total' => 7,
                    'last_page' => 7,
                ], 200);
            }

            return Http::response([
                'data' => [],
                'current_page' => 1,
                'per_page' => 1,
                'total' => 0,
                'last_page' => 1,
            ], 200);
        });

        $response = $this->actingAs($user)->get('/materials?tab=brick');

        $response->assertOk();
        $response->assertSee('Total: 181');
        $response->assertSeeInOrder([
            'Bata',
            '9',
            'Semen',
            '172',
        ], false);
    }

    public function test_material_create_forwards_payload_to_supply_be_and_redirects_back_to_family_tab(): void
    {
        $user = User::factory()->create([
            'name' => 'Supply Writer',
            'email' => 'writer@example.com',
            'auth_provider' => 'monolith',
            'auth_subject' => 'monolith:99',
            'role_snapshot' => ['supply_admin'],
            'permission_snapshot' => ['materials.create'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick' => Http::response([
                'message' => 'Material created successfully',
                'data' => [
                    'id' => 55,
                    'family' => 'brick',
                    'label' => 'Brick Charlie Tempel',
                    'brand' => 'Brick Charlie',
                    'type' => 'Tempel',
                ],
            ], 201),
        ]);

        $response = $this->actingAs($user)->post('/materials', [
            'family' => 'brick',
            'brand' => 'Brick Charlie',
            'type' => 'Tempel',
            'form' => 'Persegi',
            'dimension_length' => '21',
            'dimension_width' => '10',
            'dimension_height' => '5',
            'price_per_piece' => '1500',
        ]);

        $response->assertRedirect('/materials?family=brick');
        $response->assertSessionHas('success');

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === 'http://supply-be.test/api/v1/materials/brick'
                && $request->method() === 'POST'
                && data_get($request->data(), 'brand') === 'Brick Charlie'
                && data_get($request->data(), 'type') === 'Tempel'
                && data_get($request->data(), 'price_per_piece') === 1500.0
                && $request->hasHeader('X-Actor-Auth-Subject', 'monolith:99');
        });
    }

    public function test_materials_index_shows_recycle_bin_button_when_permission_snapshot_is_empty(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [],
                'current_page' => 1,
                'per_page' => 15,
                'total' => 0,
                'last_page' => 1,
            ], 200),
            'http://supply-be.test/api/v1/units/grouped' => Http::response([
                'success' => true,
                'data' => [
                    'brick' => [],
                    'cement' => [],
                    'nat' => [],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'steel' => [],
                    'kasa_gypsum' => [],
                    'paku_tembak' => [],
                    'paku' => [],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/materials?tab=brick');

        $response->assertOk();
        $response->assertSee(route('materials.recycle-bin'), false);
        $response->assertSee(route('stores.index'), false);
        $response->assertSee(route('units.index'), false);
        $response->assertSee(route('settings.store-search-radius.index'), false);
    }

    public function test_materials_index_keeps_modal_edit_action_and_inline_create_flow(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view', 'materials.create', 'materials.update'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'family' => 'brick',
                        'label' => 'Brick Alpha Roster',
                        'brand' => 'Alpha Brick',
                        'type' => 'Roster',
                        'form' => 'Persegi',
                        'price_per_piece' => 1200,
                        'store' => 'TB Alpha',
                    ],
                ],
                'current_page' => 1,
                'per_page' => 15,
                'total' => 1,
                'last_page' => 1,
            ], 200),
            'http://supply-be.test/api/v1/units/grouped' => Http::response([
                'success' => true,
                'data' => [
                    'brick' => [],
                    'cement' => [],
                    'nat' => [],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'steel' => [],
                    'kasa_gypsum' => [],
                    'paku_tembak' => [],
                    'paku' => [],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/materials?tab=brick');

        $response->assertOk();
        $response->assertSee(route('bricks.edit', 1), false);
        $response->assertSee('btn btn-warning btn-action open-inline-edit', false);
        $response->assertDontSee('btn btn-warning btn-action open-modal', false);
        $response->assertSee('material-inline-create-handle open-inline-create', false);
    }

    public function test_material_update_and_delete_forward_to_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.update', 'materials.delete'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick/12' => function (ClientRequest $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'data' => [
                            'id' => 12,
                            'family' => 'brick',
                            'label' => 'Brick Legacy Roster',
                            'brand' => 'Brick Legacy',
                            'type' => 'Roster',
                            'form' => 'Persegi',
                        ],
                    ], 200);
                }

                if ($request->method() === 'PUT') {
                    return Http::response([
                        'message' => 'Material updated successfully',
                        'data' => [
                            'id' => 12,
                            'family' => 'brick',
                            'label' => 'Brick Legacy Tempel',
                            'brand' => 'Brick Legacy',
                            'type' => 'Tempel',
                            'form' => 'Persegi',
                        ],
                    ], 200);
                }

                return Http::response([
                    'message' => 'Material deleted successfully',
                ], 200);
            },
        ]);

        $editResponse = $this->actingAs($user)->get('/materials/brick/12/edit');
        $editResponse->assertOk()->assertSee('Brick Legacy');

        $updateResponse = $this->actingAs($user)->put('/materials/brick/12', [
            'brand' => 'Brick Legacy',
            'type' => 'Tempel',
            'form' => 'Persegi',
        ]);

        $updateResponse->assertRedirect('/materials?family=brick');
        $updateResponse->assertSessionHas('success');

        $deleteResponse = $this->actingAs($user)->delete('/materials/brick/12');
        $deleteResponse->assertRedirect('/materials?family=brick');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PUT'
            && $request->url() === 'http://supply-be.test/api/v1/materials/brick/12'
            && data_get($request->data(), 'type') === 'Tempel');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/materials/brick/12');
    }

    public function test_legacy_delete_api_route_keeps_monolith_js_contract(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.delete'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick/12' => Http::response([
                'message' => 'Material deleted successfully',
            ], 200),
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/bricks/12');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Material berhasil dihapus.',
            ]);

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/materials/brick/12');
    }
}
