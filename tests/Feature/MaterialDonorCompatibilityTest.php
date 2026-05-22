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

        config()->set('services.monolith_auth.enabled', true);
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

        $brickShowResponse = $this->actingAs($user)->get(route('bricks.show', 12));
        $brickShowResponse->assertOk();
        $brickShowResponse->assertSee('Brick Alpha');
        $brickShowResponse->assertSee('Riwayat Perubahan');
    }

    public function test_donor_js_helper_routes_bridge_to_supply_service(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake([
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
                'data' => [
                    ['store' => 'TB Alpha'],
                    ['store' => 'TB Beta'],
                ],
            ], 200),
            'http://supply-be.test/api/v1/store-search/addresses-by-store*' => Http::response([
                'data' => [
                    ['address' => 'Jl. Mawar 1'],
                    ['address' => 'Jl. Mawar 2'],
                ],
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

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/store-search/all-stores?search=tb');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/store-search/addresses-by-store?store=TB%20Alpha&search=mawar');
    }
}
