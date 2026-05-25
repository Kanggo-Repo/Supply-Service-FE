<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreDonorPageTest extends TestCase
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
        config()->set('services.google.maps_api_key', 'test-google-key');
    }

    public function test_stores_index_renders_monolith_donor_view_with_map_preview(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores?perPage=100*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'TB Alpha', 'location_count' => 2, 'material_availability_count' => 7],
                ],
                'total' => 1,
            ], 200),
            'http://supply-be.test/api/v1/stores/1' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'TB Alpha',
                    'location_count' => 2,
                    'material_availability_count' => 7,
                    'locations' => [
                        ['id' => 11, 'store_id' => 1, 'address' => 'Jl. Mawar 1', 'resolved_address' => 'Jl. Mawar 1', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'material_availabilities_count' => 5],
                        ['id' => 12, 'store_id' => 1, 'address' => 'Jl. Melati 2', 'resolved_address' => 'Jl. Melati 2', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'material_availabilities_count' => 2],
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
                    'latitude' => -6.8915,
                    'longitude' => 107.6107,
                    'place_id' => 'place-11',
                    'formatted_address' => 'Jl. Mawar 1, Coblong, Bandung',
                    'resolved_address' => 'Jl. Mawar 1, Coblong, Bandung',
                    'contact_name' => 'Budi',
                    'contact_phone' => '08123456789',
                    'material_availabilities_count' => 5,
                ],
            ], 200),
            'http://supply-be.test/api/v1/stores/1/locations/12' => Http::response([
                'data' => [
                    'id' => 12,
                    'store_id' => 1,
                    'address' => 'Jl. Melati 2',
                    'district' => 'Sukajadi',
                    'city' => 'Bandung',
                    'province' => 'Jawa Barat',
                    'latitude' => null,
                    'longitude' => null,
                    'place_id' => null,
                    'formatted_address' => null,
                    'resolved_address' => 'Jl. Melati 2',
                    'contact_name' => '',
                    'contact_phone' => '',
                    'material_availabilities_count' => 2,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/stores');

        $response->assertOk();
        $response->assertSee('Preview Peta Semua Toko');
        $response->assertSee('Tambah Toko');
        $response->assertSee('TB Alpha');
        $response->assertSee('Bandung');
        $response->assertSee('data-google-maps-api-key="test-google-key"', false);

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/stores?perPage=100');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/stores/1');
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://supply-be.test/api/v1/stores/1/locations/11');
    }

    public function test_store_create_donor_form_can_create_initial_location_without_changing_monolith_ux(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.create'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores' => Http::response([
                'message' => 'Store created successfully',
                'data' => [
                    'id' => 77,
                    'name' => 'TB Sigma',
                    'location_count' => 0,
                    'material_availability_count' => 0,
                ],
            ], 201),
            'http://supply-be.test/api/v1/stores/77/locations' => Http::response([
                'message' => 'Store location created successfully',
                'data' => [
                    'id' => 701,
                    'store_id' => 77,
                    'address' => 'Jl. Teratai 7',
                    'city' => 'Bandung',
                    'province' => 'Jawa Barat',
                ],
            ], 201),
        ]);

        $createPage = $this->actingAs($user)->get('/stores/create');
        $createPage->assertOk();
        $createPage->assertSee('class="store-create-form"', false);
        $createPage->assertSee('/js/store-form.js', false);
        $createPage->assertSee('Cari Lokasi');

        $storeResponse = $this->actingAs($user)->post('/stores', [
            'name' => 'TB Sigma',
            'address' => 'Jl. Teratai 7',
            'district' => 'Arcamanik',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => '-6.90',
            'longitude' => '107.65',
            'place_id' => 'place-77',
            'formatted_address' => 'Jl. Teratai 7, Arcamanik, Bandung',
            'contact_name' => ['Budi'],
            'contact_phone' => ['08123456789'],
        ]);

        $storeResponse->assertRedirect('/stores');
        $storeResponse->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/stores'
            && data_get($request->data(), 'name') === 'TB Sigma');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/stores/77/locations'
            && data_get($request->data(), 'address') === 'Jl. Teratai 7'
            && data_get($request->data(), 'contact_name') === 'Budi');
    }

    public function test_store_show_and_location_modal_render_monolith_donor_views(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.view', 'stores.create', 'stores.update'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores/1' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'TB Alpha',
                    'location_count' => 1,
                    'material_availability_count' => 7,
                    'locations' => [
                        ['id' => 11, 'store_id' => 1, 'address' => 'Jl. Mawar 1', 'resolved_address' => 'Jl. Mawar 1', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'material_availabilities_count' => 7],
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
                    'latitude' => -6.8915,
                    'longitude' => 107.6107,
                    'place_id' => 'place-11',
                    'formatted_address' => 'Jl. Mawar 1, Coblong, Bandung',
                    'resolved_address' => 'Jl. Mawar 1, Coblong, Bandung',
                    'contact_name' => 'Budi',
                    'contact_phone' => '08123456789',
                    'material_availabilities_count' => 7,
                ],
            ], 200),
        ]);

        $showPage = $this->actingAs($user)->get('/stores/1');
        $showPage->assertOk();
        $showPage->assertSee('Daftar Cabang &amp; Lokasi', false);
        $showPage->assertSee('Tambah Lokasi');
        $showPage->assertSee('Budi');
        $showPage->assertSee('7 Material');

        $locationCreateModal = $this->actingAs($user)->get('/stores/1/locations/create');
        $locationCreateModal->assertOk();
        $locationCreateModal->assertSee('class="store-location-form"', false);
        $locationCreateModal->assertSee('/js/store-location-form.js', false);
        $locationCreateModal->assertSee('TB Alpha');
    }

    public function test_store_update_and_delete_forward_to_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.update', 'stores.delete', 'stores.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores/9' => function (ClientRequest $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'data' => [
                            'id' => 9,
                            'name' => 'TB Delta',
                            'locations' => [],
                            'location_count' => 0,
                            'material_availability_count' => 0,
                        ],
                    ], 200);
                }

                if ($request->method() === 'PUT') {
                    return Http::response([
                        'message' => 'Store updated successfully',
                        'data' => [
                            'id' => 9,
                            'name' => 'TB Delta Baru',
                        ],
                    ], 200);
                }

                return Http::response([
                    'message' => 'Store deleted successfully',
                ], 200);
            },
        ]);

        $editPage = $this->actingAs($user)->get('/stores/9/edit');
        $editPage->assertOk()->assertSee('TB Delta');

        $updateResponse = $this->actingAs($user)->put('/stores/9', [
            'name' => 'TB Delta Baru',
        ]);
        $updateResponse->assertRedirect('/stores');
        $updateResponse->assertSessionHas('success');

        $deleteResponse = $this->actingAs($user)->delete('/stores/9');
        $deleteResponse->assertRedirect('/stores');
        $deleteResponse->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PUT'
            && $request->url() === 'http://supply-be.test/api/v1/stores/9'
            && data_get($request->data(), 'name') === 'TB Delta Baru');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/stores/9');
    }
}
