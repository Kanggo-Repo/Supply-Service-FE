<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreLocationMaterialsDonorPageTest extends TestCase
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

    public function test_store_location_materials_page_renders_monolith_donor_view_with_grouped_tabs(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.view', 'materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores/1' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'TB Alpha',
                    'location_count' => 1,
                    'material_availability_count' => 3,
                    'locations' => [
                        ['id' => 11, 'store_id' => 1, 'address' => 'Jl. Mawar 1'],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/stores/1/locations/11' => Http::response([
                'data' => [
                    'id' => 11,
                    'store_id' => 1,
                    'address' => 'Jl. Mawar 1',
                    'district' => 'Coblong',
                    'city' => 'Bandung',
                    'province' => 'Jawa Barat',
                    'material_availabilities_count' => 3,
                ],
            ], 200),
            'http://supply-be.test/api/v1/stores/1/locations/11/materials' => Http::response([
                'data' => [
                    [
                        'type' => 'cement',
                        'label' => 'Semen',
                        'count' => 1,
                        'items' => [
                            [
                                'id' => 21,
                                'brand' => 'Semen Alpha',
                                'type' => 'Portland',
                                'cement_name' => 'Semen Alpha Portland',
                                'package_unit' => 'sak',
                                'package_weight_net' => 40,
                                'store' => 'TB Alpha',
                                'address' => 'Jl. Mawar 1',
                                'label' => 'Semen Alpha Portland',
                            ],
                        ],
                    ],
                    [
                        'type' => 'nat',
                        'label' => 'Nat',
                        'count' => 1,
                        'items' => [
                            [
                                'id' => 22,
                                'brand' => 'Nat Putih',
                                'type' => 'Nat',
                                'nat_name' => 'Nat Putih Premium',
                                'package_unit' => 'kg',
                                'package_weight_net' => 5,
                                'store' => 'TB Alpha',
                                'address' => 'Jl. Mawar 1',
                                'label' => 'Nat Putih Premium',
                            ],
                        ],
                    ],
                    [
                        'type' => 'brick',
                        'label' => 'Bata',
                        'count' => 1,
                        'items' => [
                            [
                                'id' => 31,
                                'brand' => 'Brick Alpha',
                                'type' => 'Roster',
                                'form' => 'Persegi',
                                'dimension_length' => 20,
                                'dimension_width' => 10,
                                'dimension_height' => 5,
                                'package_volume' => 0.001,
                                'price_per_piece' => 1200,
                                'comparison_price_per_m3' => 1500000,
                                'store' => 'TB Alpha',
                                'address' => 'Jl. Mawar 1',
                                'label' => 'Brick Alpha Roster',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/stores/1/locations/11/materials');

        $response->assertOk();
        $response->assertSee('Material TB Alpha');
        $response->assertSee('Kembali');
        $response->assertSee('Bata');
        $response->assertSee('Semen');
        $response->assertSee('Brick Alpha');
        $response->assertSee('Nat Putih Premium');
        $response->assertSee('data-tab="brick"', false);
        $response->assertSee('data-tab="cement"', false);
        $response->assertDontSee('data-tab="cat"', false);
        $response->assertDontSee('data-tab="sand"', false);
        $response->assertSee('storeLocationMaterialActiveTab');
        $response->assertSee('materialChoiceModal');

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/stores/1/locations/11/materials');
    }

    public function test_store_location_materials_page_applies_search_and_sort_like_monolith(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.view', 'materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores/1' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'TB Alpha',
                    'location_count' => 1,
                    'material_availability_count' => 2,
                    'locations' => [
                        ['id' => 11, 'store_id' => 1, 'address' => 'Jl. Mawar 1'],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/stores/1/locations/11' => Http::response([
                'data' => [
                    'id' => 11,
                    'store_id' => 1,
                    'address' => 'Jl. Mawar 1',
                    'city' => 'Bandung',
                    'province' => 'Jawa Barat',
                    'material_availabilities_count' => 2,
                ],
            ], 200),
            'http://supply-be.test/api/v1/stores/1/locations/11/materials' => Http::response([
                'data' => [
                    [
                        'type' => 'brick',
                        'label' => 'Bata',
                        'count' => 2,
                        'items' => [
                            [
                                'id' => 31,
                                'brand' => 'Brick Alpha',
                                'type' => 'Roster',
                                'form' => 'Persegi',
                                'dimension_length' => 20,
                                'dimension_width' => 10,
                                'dimension_height' => 5,
                                'package_volume' => 0.001,
                                'price_per_piece' => 1200,
                                'comparison_price_per_m3' => 1500000,
                                'store' => 'TB Alpha',
                                'address' => 'Jl. Mawar 1',
                                'label' => 'Brick Alpha Roster',
                            ],
                            [
                                'id' => 32,
                                'brand' => 'Brick Zeta',
                                'type' => 'Tempel',
                                'form' => 'Tempel',
                                'dimension_length' => 22,
                                'dimension_width' => 11,
                                'dimension_height' => 4,
                                'package_volume' => 0.0012,
                                'price_per_piece' => 1800,
                                'comparison_price_per_m3' => 1700000,
                                'store' => 'TB Alpha',
                                'address' => 'Jl. Mawar 1',
                                'label' => 'Brick Zeta Tempel',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $searchResponse = $this->actingAs($user)->get('/stores/1/locations/11/materials?search=zeta');
        $searchResponse->assertOk();
        $searchResponse->assertSee('Brick Zeta');
        $searchResponse->assertDontSee('Brick Alpha');

        $sortResponse = $this->actingAs($user)->get('/stores/1/locations/11/materials?sort_by=brand&sort_direction=desc');
        $sortResponse->assertOk();
        $sortResponse->assertSeeInOrder(['Brick Zeta', 'Brick Alpha']);
    }
}
