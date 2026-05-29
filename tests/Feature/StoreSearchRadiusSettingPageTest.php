<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreSearchRadiusSettingPageTest extends TestCase
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

    public function test_store_search_radius_setting_page_renders_donor_view(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['store-search-radius.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/settings/store-search-radius' => Http::response([
                'data' => [
                    'project_store_radius_default_km' => 10,
                    'project_store_radius_final_km' => 15,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('settings.store-search-radius.index'));

        $response->assertOk();
        $response->assertSeeText('Radius Pencarian Toko');
        $response->assertSeeText('Radius Proyek Default (km)');
        $response->assertSeeText('Radius Ke-2 / Batas Akhir (km)');
    }

    public function test_store_search_radius_setting_update_forwards_to_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['store-search-radius.update'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/settings/store-search-radius' => Http::response([
                'message' => 'Store search radius updated successfully',
                'data' => [
                    'project_store_radius_default_km' => 12.5,
                    'project_store_radius_final_km' => 18,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('settings.store-search-radius.store'), [
            'project_store_radius_default_km' => 12.5,
            'project_store_radius_final_km' => 18,
        ]);

        $response->assertRedirect(route('settings.store-search-radius.index'));
        $response->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PUT'
            && $request->url() === 'http://supply-be.test/api/v1/settings/store-search-radius'
            && data_get($request->data(), 'project_store_radius_default_km') === 12.5
            && data_get($request->data(), 'project_store_radius_final_km') === 18);
    }
}
