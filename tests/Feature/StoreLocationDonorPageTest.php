<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreLocationDonorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.supply_service.base_url', 'http://supply-be.test');
        config()->set('services.supply_service.service_name', 'supply-fe');
        config()->set('services.supply_service.token', 'local-supply-token');
        config()->set('services.supply_service.verify_ssl', false);
        config()->set('services.google.maps_api_key', 'test-google-key');
    }

    public function test_store_location_edit_update_and_delete_follow_donor_flow(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['stores.view', 'stores.update', 'stores.delete'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores/7' => Http::response([
                'data' => [
                    'id' => 7,
                    'name' => 'TB Lokasi',
                    'locations' => [
                        ['id' => 71, 'store_id' => 7, 'address' => 'Jl. Kenanga 7', 'resolved_address' => 'Jl. Kenanga 7'],
                    ],
                    'location_count' => 1,
                    'material_availability_count' => 0,
                ],
            ], 200),
            'http://supply-be.test/api/v1/stores/7/locations/71' => function (ClientRequest $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'data' => [
                            'id' => 71,
                            'store_id' => 7,
                            'address' => 'Jl. Kenanga 7',
                            'district' => 'Coblong',
                            'city' => 'Bandung',
                            'province' => 'Jawa Barat',
                            'latitude' => -6.91,
                            'longitude' => 107.62,
                            'place_id' => 'place-71',
                            'formatted_address' => 'Jl. Kenanga 7, Coblong, Bandung',
                            'resolved_address' => 'Jl. Kenanga 7, Coblong, Bandung',
                            'contact_name' => 'Dina',
                            'contact_phone' => '08123456789',
                            'material_availabilities_count' => 0,
                        ],
                    ], 200);
                }

                if ($request->method() === 'PUT') {
                    return Http::response([
                        'message' => 'Store location updated successfully',
                        'data' => [
                            'id' => 71,
                            'store_id' => 7,
                            'address' => 'Jl. Kenanga 8',
                        ],
                    ], 200);
                }

                return Http::response([
                    'message' => 'Store location deleted successfully',
                ], 200);
            },
        ]);

        $editPage = $this->actingAs($user)->get('/stores/7/locations/71/edit');
        $editPage->assertOk();
        $editPage->assertSee('class="store-location-form"', false);
        $editPage->assertSee('TB Lokasi');
        $editPage->assertSee('Jl. Kenanga 7');

        $updateResponse = $this->actingAs($user)->put('/stores/7/locations/71', [
            'address' => 'Jl. Kenanga 8',
            'district' => 'Coblong',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => '-6.91',
            'longitude' => '107.62',
            'place_id' => 'place-71',
            'formatted_address' => 'Jl. Kenanga 8, Coblong, Bandung',
            'contact_name' => ['Dina'],
            'contact_phone' => ['08123456789'],
        ]);
        $updateResponse->assertRedirect('/stores/7');
        $updateResponse->assertSessionHas('success');

        $deleteResponse = $this->actingAs($user)->delete('/stores/7/locations/71');
        $deleteResponse->assertRedirect('/stores/7');
        $deleteResponse->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PUT'
            && $request->url() === 'http://supply-be.test/api/v1/stores/7/locations/71'
            && data_get($request->data(), 'address') === 'Jl. Kenanga 8');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/stores/7/locations/71');
    }
}
