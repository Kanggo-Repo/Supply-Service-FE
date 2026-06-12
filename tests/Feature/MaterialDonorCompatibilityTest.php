<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaterialDonorCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.supply_service.base_url', 'http://supply-be.test');
        config()->set('services.supply_service.service_name', 'supply-fe');
        config()->set('services.supply_service.token', 'local-supply-token');
        config()->set('services.supply_service.verify_ssl', false);
    }

    public function test_donor_family_pages_render_monolith_views_and_scripts(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.create', 'materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/units/grouped' => Http::response([
                'success' => true,
                'data' => [
                    'brick' => [],
                    'cement' => [
                        ['id' => 8, 'code' => 'sak', 'name' => 'Sak'],
                    ],
                    'nat' => [
                        ['id' => 9, 'code' => 'kg', 'name' => 'Kilogram'],
                    ],
                    'sand' => [],
                    'cat' => [],
                    'ceramic' => [],
                    'steel' => [],
                    'kasa_gypsum' => [],
                    'paku_tembak' => [],
                    'paku' => [],
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/brick/12' => Http::response([
                'data' => [
                    'id' => 12,
                    'material_name' => 'Brick Alpha Roster',
                    'type' => 'Roster',
                    'brand' => 'Brick Alpha',
                    'form' => 'Persegi',
                    'store' => 'TB Alpha',
                    'address' => 'Jl. Mawar',
                    'price_per_piece' => 1200,
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/brick/12/history' => Http::response([
                'data' => [
                    [
                        'id' => 200,
                        'action' => 'updated',
                        'edited_at' => '2026-05-22T08:30:00+07:00',
                        'user' => ['name' => 'Operator Supply'],
                        'changes' => [
                            'brand' => ['from' => 'Brick Lama', 'to' => 'Brick Alpha'],
                        ],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/nat/18' => Http::response([
                'data' => [
                    'id' => 18,
                    'nat_name' => 'Nat Putih Premium',
                    'type' => 'Nat Keramik',
                    'brand' => 'Nat Alpha',
                    'sub_brand' => 'Premium',
                    'store' => 'TB Nat',
                    'address' => 'Jl. Nat 1',
                    'package_unit' => 'kg',
                    'package_weight_gross' => 5,
                    'package_price' => 45000,
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/nat/18/history' => Http::response([
                'data' => [
                    [
                        'id' => 280,
                        'action' => 'updated',
                        'edited_at' => '2026-05-22T09:00:00+07:00',
                        'user' => ['name' => 'Operator Supply'],
                        'changes' => [
                            'brand' => ['from' => 'Nat Lama', 'to' => 'Nat Alpha'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $brickCreateResponse = $this->actingAs($user)->get(route('bricks.create'));
        $brickCreateResponse->assertOk();
        $brickCreateResponse->assertSee('id="brickForm"', false);
        $brickCreateResponse->assertSee('/js/brick-form.js', false);
        $brickCreateResponse->assertSee('/js/store-autocomplete.js', false);

        $cementCreateResponse = $this->actingAs($user)->get(route('cements.create'));
        $cementCreateResponse->assertOk();
        $cementCreateResponse->assertSee('id="cementForm"', false);
        $cementCreateResponse->assertSee('Sak');
        $cementCreateResponse->assertSee('/js/cement-form.js', false);

        $natCreateResponse = $this->actingAs($user)->get(route('nats.create'));
        $natCreateResponse->assertOk();
        $natCreateResponse->assertSee('id="natForm"', false);
        $natCreateResponse->assertSee('kg');
        $natCreateResponse->assertSee('/js/nat-form.js', false);

        $brickShowResponse = $this->actingAs($user)->get(route('bricks.show', 12));
        $brickShowResponse->assertOk();
        $brickShowResponse->assertSee('Brick Alpha');
        $brickShowResponse->assertSee('Riwayat Perubahan');

        $natShowResponse = $this->actingAs($user)->get(route('nats.show', 18));
        $natShowResponse->assertOk();
        $natShowResponse->assertSee('Nat Putih Premium');
        $natShowResponse->assertSee('Riwayat Perubahan');
    }

    public function test_donor_js_helper_routes_bridge_to_supply_service(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/reference/materials/filter-metadata?family=brick&fields%5B0%5D=type' => Http::response([
                'data' => [
                    'family' => 'brick',
                    'fields' => [
                        'type' => ['Roster', 'Tempel'],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/brick?perPage=500' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'type' => 'Roster',
                        'brand' => 'Brick Alpha',
                        'form' => 'Persegi',
                        'store' => 'TB Alpha',
                        'address' => 'Jl. Mawar 1',
                    ],
                    [
                        'id' => 2,
                        'type' => 'Tempel',
                        'brand' => 'Brick Beta',
                        'form' => 'Tempel',
                        'store' => 'TB Beta',
                        'address' => 'Jl. Melati 2',
                    ],
                ],
                'total' => 2,
            ], 200),
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'type' => 'Roster',
                        'brand' => 'Brick Alpha',
                        'form' => 'Persegi',
                        'store' => 'TB Alpha',
                        'address' => 'Jl. Mawar 1',
                    ],
                    [
                        'id' => 2,
                        'type' => 'Tempel',
                        'brand' => 'Brick Beta',
                        'form' => 'Tempel',
                        'store' => 'TB Beta',
                        'address' => 'Jl. Melati 2',
                    ],
                ],
                'total' => 2,
            ], 200),
            'http://supply-be.test/api/v1/store-search/all-stores*' => Http::response([
                'TB Alpha',
                'TB Beta',
            ], 200),
            'http://supply-be.test/api/v1/store-search/addresses-by-store*' => Http::response([
                'Jl. Mawar 1',
                'Jl. Mawar 2',
            ], 200),
            'http://supply-be.test/api/v1/store-search/locations-by-store*' => Http::response([
                [
                    'id' => 9,
                    'store_name' => 'TB Alpha',
                    'address' => 'Jl. Mawar 1',
                    'resolved_address' => 'Jl. Mawar 1',
                ],
            ], 200),
            'http://supply-be.test/api/v1/store-search/quick-create' => Http::response([
                'id' => 9,
                'store_name' => 'TB Alpha',
                'address' => 'Jl. Mawar 1',
                'resolved_address' => 'Jl. Mawar 1',
                'display_text' => 'TB Alpha - Jl. Mawar 1',
            ], 200),
        ]);

        $fieldValuesResponse = $this->actingAs($user)->getJson('/api/bricks/field-values/type?search=ro&limit=20');
        $fieldValuesResponse
            ->assertOk()
            ->assertExactJson(['Roster']);

        $allStoresResponse = $this->actingAs($user)->getJson('/api/bricks/all-stores?search=tb');
        $allStoresResponse
            ->assertOk()
            ->assertExactJson(['TB Alpha', 'TB Beta']);

        $addressesResponse = $this->actingAs($user)->getJson('/api/bricks/addresses-by-store?store=TB Alpha&search=mawar');
        $addressesResponse
            ->assertOk()
            ->assertExactJson(['Jl. Mawar 1', 'Jl. Mawar 2']);

        $locationsResponse = $this->actingAs($user)->getJson('/api/stores/locations-by-store?store=TB Alpha&limit=20');
        $locationsResponse
            ->assertOk()
            ->assertJsonPath('0.id', 9)
            ->assertJsonPath('0.store_name', 'TB Alpha');

        $quickCreateResponse = $this->actingAs($user)->postJson('/api/stores/quick-create', [
            'input' => 'TB Alpha - Jl. Mawar 1',
        ]);
        $quickCreateResponse
            ->assertOk()
            ->assertJsonPath('id', 9)
            ->assertJsonPath('display_text', 'TB Alpha - Jl. Mawar 1');

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/store-search/all-stores?search=tb');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/store-search/addresses-by-store?store=TB%20Alpha&search=mawar');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/store-search/locations-by-store?store=TB%20Alpha&limit=20');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/reference/materials/filter-metadata?family=brick&fields%5B0%5D=type');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/store-search/quick-create'
            && $request->method() === 'POST'
            && data_get($request->data(), 'input') === 'TB Alpha - Jl. Mawar 1');
    }

    public function test_history_restore_route_bridges_to_supply_service(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.update'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick/12/history/200/restore' => Http::response([
                'message' => 'Material history restored successfully',
                'changed' => true,
                'data' => [
                    'id' => 12,
                    'family' => 'brick',
                ],
            ], 200),
        ]);

        $response = $this->from('/bricks/12')->actingAs($user)->post('/bricks/12/history/200/restore');

        $response->assertRedirect(route('materials.index', ['tab' => 'brick']));
        $response->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/materials/brick/12/history/200/restore');
    }

    public function test_donor_store_normalizes_grouped_integer_price_payload_before_forwarding(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.create'],
            'auth_provider' => 'monolith',
            'auth_subject' => 'monolith:44',
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/cement' => Http::response([
                'message' => 'Material created successfully',
                'data' => [
                    'id' => 88,
                    'family' => 'cement',
                    'brand' => 'Semen Baru',
                    'package_price' => 35000,
                ],
            ], 201),
        ]);

        $response = $this->actingAs($user)->postJson('/cements', [
            'brand' => 'Semen Baru',
            'type' => 'Mortar',
            'package_price' => '35.000',
            'package_weight_net' => '40,5',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === 'http://supply-be.test/api/v1/materials/cement'
                && data_get($request->data(), 'package_price') === 35000.0
                && data_get($request->data(), 'package_weight_net') === 40.5;
        });
    }

    public function test_inline_cat_store_composes_required_name_and_volume_unit_before_forwarding(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.create'],
            'auth_provider' => 'monolith',
            'auth_subject' => 'monolith:44',
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/cat' => Http::response([
                'message' => 'Material created successfully',
                'data' => [
                    'id' => 89,
                    'family' => 'cat',
                    'cat_name' => 'Interior Avian Supersilk Putih 5L',
                ],
            ], 201),
        ]);

        $response = $this->actingAs($user)->postJson('/cats', [
            'type' => 'Interior',
            'brand' => 'Avian',
            'sub_brand' => 'Supersilk',
            'color_name' => 'Putih',
            'volume' => '5',
            'purchase_price' => '125.000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === 'http://supply-be.test/api/v1/materials/cat'
                && data_get($request->data(), 'cat_name') === 'Interior Avian Supersilk Putih 5L'
                && data_get($request->data(), 'volume_unit') === 'L'
                && data_get($request->data(), 'purchase_price') === 125000.0;
        });
    }
}
