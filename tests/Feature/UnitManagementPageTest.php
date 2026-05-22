<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnitManagementPageTest extends TestCase
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

    public function test_units_index_reads_list_and_material_type_filters_from_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['units.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/units?*' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'id' => 8,
                        'code' => 'sak',
                        'name' => 'Sak',
                        'package_weight' => 40,
                        'description' => 'Kemasan semen',
                        'material_types' => ['cement', 'sand'],
                    ],
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 1,
                    'last_page' => 1,
                ],
            ], 200),
            'http://supply-be.test/api/v1/units/material-types' => Http::response([
                'success' => true,
                'data' => [
                    ['value' => 'brick', 'label' => 'Bata'],
                    ['value' => 'cement', 'label' => 'Semen'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/units?material_type=cement');

        $response->assertOk();
        $response->assertSee('Database Unit');
        $response->assertSee('Sak');
        $response->assertSee('Semen');
    }

    public function test_unit_create_update_and_delete_forward_to_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['units.create', 'units.update', 'units.delete'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/units/material-types' => Http::response([
                'success' => true,
                'data' => [
                    ['value' => 'cement', 'label' => 'Semen'],
                    ['value' => 'sand', 'label' => 'Pasir'],
                    ['value' => 'cat', 'label' => 'Cat'],
                ],
            ], 200),
            'http://supply-be.test/api/v1/units' => function (ClientRequest $request) {
                if ($request->method() === 'POST') {
                    return Http::response([
                        'success' => true,
                        'message' => 'Unit created successfully',
                        'data' => [
                            'id' => 44,
                            'code' => 'sak',
                            'name' => 'Sak',
                            'package_weight' => 40,
                            'description' => 'Kemasan semen',
                            'material_types' => ['cement', 'sand'],
                        ],
                    ], 201);
                }

                return Http::response([], 500);
            },
            'http://supply-be.test/api/v1/units/44' => function (ClientRequest $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'success' => true,
                        'data' => [
                            'id' => 44,
                            'code' => 'sak',
                            'name' => 'Sak',
                            'package_weight' => 40,
                            'description' => 'Kemasan semen',
                            'material_types' => ['cement', 'sand'],
                        ],
                    ], 200);
                }

                if ($request->method() === 'PUT') {
                    return Http::response([
                        'success' => true,
                        'message' => 'Unit updated successfully',
                        'data' => [
                            'id' => 44,
                            'code' => 'kg',
                            'name' => 'Kilogram',
                            'package_weight' => 1,
                            'description' => 'Kemasan kilogram',
                            'material_types' => ['cat'],
                        ],
                    ], 200);
                }

                return Http::response([
                    'success' => true,
                    'message' => 'Unit deleted successfully',
                ], 200);
            },
        ]);

        $createResponse = $this->actingAs($user)->post('/units', [
            'code' => 'sak',
            'name' => 'Sak',
            'package_weight' => '40',
            'description' => 'Kemasan semen',
            'material_types' => ['cement', 'sand'],
        ]);

        $createResponse->assertRedirect('/units');

        $editResponse = $this->actingAs($user)->get('/units/44/edit');
        $editResponse->assertOk()->assertSee('Sak');

        $updateResponse = $this->actingAs($user)->put('/units/44', [
            'code' => 'kg',
            'name' => 'Kilogram',
            'package_weight' => '1',
            'description' => 'Kemasan kilogram',
            'material_types' => ['cat'],
        ]);

        $updateResponse->assertRedirect('/units');

        $deleteResponse = $this->actingAs($user)->delete('/units/44');
        $deleteResponse->assertRedirect('/units');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'POST'
            && $request->url() === 'http://supply-be.test/api/v1/units'
            && data_get($request->data(), 'code') === 'sak');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'PUT'
            && $request->url() === 'http://supply-be.test/api/v1/units/44'
            && data_get($request->data(), 'material_types.0') === 'cat');

        Http::assertSent(fn (ClientRequest $request) => $request->method() === 'DELETE'
            && $request->url() === 'http://supply-be.test/api/v1/units/44');
    }
}
