<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaterialRecycleBinDonorPageTest extends TestCase
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

    public function test_recycle_bin_page_renders_monolith_donor_view(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [
                'materials.recycle-bin.view',
                'materials.recycle-bin.restore',
                'materials.recycle-bin.delete',
            ],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/recycle-bin' => Http::response([
                'data' => [
                    'summary' => [
                        'brick' => 1,
                        'cement' => 1,
                    ],
                    'items' => [
                        [
                            'id' => 31,
                            'family' => 'brick',
                            'material_type' => 'brick',
                            'row_material_type' => 'brick',
                            'material_kind' => 'brick',
                            'brand' => 'Brick Recycle',
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
                            'deleted_by_name' => 'Supply Operator',
                            'deleted_at_formatted' => '25-05-2026 10:30:00',
                        ],
                        [
                            'id' => 32,
                            'family' => 'cement',
                            'material_type' => 'cement',
                            'row_material_type' => 'cement',
                            'material_kind' => 'cement',
                            'brand' => 'Semen Recycle',
                            'type' => 'Portland',
                            'sub_brand' => 'Premium',
                            'code' => 'PC',
                            'color' => 'Abu',
                            'package_unit' => 'sak',
                            'package_weight_net' => 40,
                            'package_price' => 65000,
                            'comparison_price_per_kg' => 1625,
                            'store' => 'TB Beta',
                            'address' => 'Jl. Melati 2',
                            'deleted_by_name' => 'Supply Operator',
                            'deleted_at_formatted' => '25-05-2026 10:25:00',
                        ],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/units/grouped' => Http::response([
                'data' => [
                    'cement' => [
                        ['id' => 1, 'code' => 'sak', 'name' => 'Sak'],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/materials/recycle-bin');

        $response->assertOk();
        $response->assertSee('Recycle Bin Material');
        $response->assertSee('Brick Recycle');
        $response->assertSee('Semen Recycle');
        $response->assertSee('0 dipilih');
        $response->assertSee('recycleBulkRestoreForm');
        $response->assertSee('recycleBulkDeleteForm');
    }

    public function test_restore_single_recycle_item_calls_supply_service_and_redirects_back(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [
                'materials.recycle-bin.view',
                'materials.recycle-bin.restore',
            ],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/recycle-bin' => Http::response([
                'data' => [
                    'summary' => ['brick' => 1],
                    'items' => [
                        [
                            'id' => 31,
                            'family' => 'brick',
                            'material_type' => 'brick',
                            'deleted_by' => ['id' => 99, 'name' => 'Supply Operator'],
                        ],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/recycle-bin/brick/31/restore' => Http::response([
                'message' => 'Material restored successfully',
                'data' => ['id' => 31, 'family' => 'brick'],
            ], 200),
        ]);

        $response = $this->from('/materials/recycle-bin')->actingAs($user)->post('/materials/brick/31/restore');

        $response->assertRedirect('/materials/recycle-bin');
        $response->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/materials/recycle-bin/brick/31/restore');
    }

    public function test_bulk_force_delete_loops_through_selected_items(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [
                'materials.recycle-bin.view',
                'materials.recycle-bin.delete',
            ],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/recycle-bin/brick/31' => Http::response([
                'message' => 'Material permanently deleted',
            ], 200),
            'http://supply-be.test/api/v1/materials/recycle-bin/cement/32' => Http::response([
                'message' => 'Material permanently deleted',
            ], 200),
        ]);

        $response = $this->from('/materials/recycle-bin')->actingAs($user)->post('/materials/bulk/force-delete', [
            'items' => [
                ['type' => 'brick', 'id' => 31],
                ['type' => 'cement', 'id' => 32],
            ],
        ]);

        $response->assertRedirect('/materials/recycle-bin');
        $response->assertSessionHas('success');

        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/materials/recycle-bin/brick/31');
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/materials/recycle-bin/cement/32');
    }

    public function test_bulk_restore_loops_through_selected_items(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [
                'materials.recycle-bin.view',
                'materials.recycle-bin.restore',
            ],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/recycle-bin' => Http::response([
                'data' => [
                    'summary' => ['brick' => 2],
                    'items' => [
                        [
                            'id' => 31,
                            'family' => 'brick',
                            'material_type' => 'brick',
                            'deleted_by' => ['id' => 999, 'name' => 'Supply Operator'],
                        ],
                        [
                            'id' => 32,
                            'family' => 'brick',
                            'material_type' => 'brick',
                            'deleted_by' => ['id' => 999, 'name' => 'Supply Operator'],
                        ],
                    ],
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/recycle-bin/brick/31/restore' => Http::response([
                'message' => 'Material restored successfully',
            ], 200),
            'http://supply-be.test/api/v1/materials/recycle-bin/brick/32/restore' => Http::response([
                'message' => 'Material restored successfully',
            ], 200),
        ]);

        $response = $this->from('/materials/recycle-bin')->actingAs($user)->post('/materials/bulk/restore', [
            'items' => [
                ['type' => 'brick', 'id' => 31],
                ['type' => 'brick', 'id' => 32],
            ],
        ]);

        $response->assertRedirect('/materials/recycle-bin');
        $response->assertSessionHas('success');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/materials/recycle-bin/brick/31/restore');
        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/materials/recycle-bin/brick/32/restore');
    }

    public function test_restore_route_requires_recycle_restore_permission_like_monolith(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [
                'materials.recycle-bin.view',
            ],
        ]);

        $this->actingAs($user)
            ->post('/materials/brick/31/restore')
            ->assertForbidden();
    }
}
