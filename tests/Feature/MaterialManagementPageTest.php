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
            'http://supply-be.test/api/v1/materials/summary' => Http::response([
                'data' => [
                    'families' => [
                        'brick' => 1,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'nat' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_families' => [
                        'brick' => 1,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_letters' => [
                        'brick' => ['A'],
                        'cement' => [],
                        'sand' => [],
                        'cat' => [],
                        'ceramic' => [],
                        'steel' => [],
                        'kasa_gypsum' => [],
                        'paku_tembak' => [],
                        'paku' => [],
                    ],
                    'grand_total' => 1,
                ],
            ], 200),
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
        $response->assertSee('data-material-tabs-scroll', false);

        Http::assertSent(function (ClientRequest $request) {
            if (! str_starts_with($request->url(), 'http://supply-be.test/api/v1/materials/brick')) {
                return false;
            }

            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return ($query['search'] ?? null) === 'Alpha'
                && (int) ($query['page'] ?? 0) === 1
                && (int) ($query['perPage'] ?? 0) === 50;
        });
    }

    public function test_materials_index_renders_full_letter_navigation_from_summary_even_when_chunk_data_is_partial(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/summary' => Http::response([
                'data' => [
                    'families' => [
                        'brick' => 60,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'nat' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_families' => [
                        'brick' => 60,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_letters' => [
                        'brick' => ['A', 'C', 'S'],
                        'cement' => [],
                        'sand' => [],
                        'cat' => [],
                        'ceramic' => [],
                        'steel' => [],
                        'kasa_gypsum' => [],
                        'paku_tembak' => [],
                        'paku' => [],
                    ],
                    'display_letter_pages' => [
                        'brick' => ['A' => 1, 'C' => 1, 'S' => 2],
                        'cement' => [],
                        'sand' => [],
                        'cat' => [],
                        'ceramic' => [],
                        'steel' => [],
                        'kasa_gypsum' => [],
                        'paku_tembak' => [],
                        'paku' => [],
                    ],
                    'grand_total' => 60,
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'family' => 'brick',
                        'label' => 'Alpha Brick',
                        'brand' => 'Alpha Brick',
                        'type' => 'Roster',
                    ],
                ],
                'current_page' => 1,
                'per_page' => 50,
                'total' => 60,
                'last_page' => 2,
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
        $response->assertSee('#brick-letter-A', false);
        $response->assertSee('#brick-letter-C', false);
        $response->assertSee('#brick-letter-S', false);
        $response->assertSee('data-letter-pages=', false);
    }

    public function test_material_tab_endpoint_forwards_chunk_page_to_supply_be(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [
                    [
                        'id' => 51,
                        'family' => 'brick',
                        'label' => 'Brick Chunked Roster',
                        'brand' => 'Brick Chunked',
                        'type' => 'Roster',
                    ],
                ],
                'current_page' => 2,
                'per_page' => 50,
                'total' => 120,
                'last_page' => 3,
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

        $response = $this->actingAs($user)->get('/materials/tab/brick?page=2');

        $response->assertOk();
        $response->assertSee('Brick Chunked');
        $response->assertSee('data-next-page="3"', false);

        Http::assertSent(function (ClientRequest $request) {
            if (! str_starts_with($request->url(), 'http://supply-be.test/api/v1/materials/brick')) {
                return false;
            }

            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            return (int) ($query['page'] ?? 0) === 2
                && (int) ($query['perPage'] ?? 0) === 50;
        });
    }

    public function test_materials_index_uses_all_family_totals_for_tab_badges_and_topbar(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake(function (ClientRequest $request) {
            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';

            if ($path === '/api/v1/materials/summary') {
                return Http::response([
                    'data' => [
                        'families' => [
                            'brick' => 9,
                            'cement' => 165,
                            'sand' => 0,
                            'cat' => 0,
                            'ceramic' => 0,
                            'nat' => 7,
                            'steel' => 0,
                            'kasa_gypsum' => 0,
                            'paku_tembak' => 0,
                            'paku' => 0,
                        ],
                        'display_families' => [
                            'brick' => 9,
                            'cement' => 172,
                            'sand' => 0,
                            'cat' => 0,
                            'ceramic' => 0,
                            'steel' => 0,
                            'kasa_gypsum' => 0,
                            'paku_tembak' => 0,
                            'paku' => 0,
                        ],
                        'grand_total' => 181,
                    ],
                ], 200);
            }

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

        Http::assertSentCount(3);
    }

    public function test_materials_index_shows_empty_search_message_when_active_tab_has_no_results(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/summary' => Http::response([
                'data' => [
                    'families' => [
                        'brick' => 0,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'nat' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_families' => [
                        'brick' => 0,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'grand_total' => 0,
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [],
                'current_page' => 1,
                'per_page' => 50,
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

        $response = $this->actingAs($user)->get('/materials?tab=brick&search=zzz');

        $response->assertOk();
        $response->assertSee('Tidak ada hasil pencarian di tab ini.');
    }

    public function test_materials_index_shows_store_map_warning_and_sidebar_badge_like_monolith(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => ['materials.view', 'stores.view'],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/stores/sidebar-summary' => Http::response([
                'data' => [
                    'stores_missing_map_count' => 3,
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/summary' => Http::response([
                'data' => [
                    'families' => [
                        'brick' => 1,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'nat' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_families' => [
                        'brick' => 1,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'grand_total' => 1,
                ],
            ], 200),
            'http://supply-be.test/api/v1/materials/brick*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'family' => 'brick',
                        'label' => 'Brick Alpha Roster',
                        'brand' => 'Alpha Brick',
                        'type' => 'Roster',
                        'store' => 'TB Alpha',
                        'address' => 'Jl. Mawar 1',
                        'has_missing_map_coordinates' => true,
                        'map_warning_reason' => 'Koordinat Google Maps toko ini belum diisi.',
                        'map_warning_action_context' => 'store-location-edit',
                        'map_warning_store_id' => 7,
                        'map_warning_store_location_id' => 71,
                    ],
                ],
                'current_page' => 1,
                'per_page' => 50,
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
        $response->assertSee('title="3 toko belum memiliki koordinat map"', false);
        $response->assertSee('Koordinat Google Maps toko ini belum diisi.');
        $response->assertSee('/stores/7/locations/71/edit?_redirect_url=', false);
        $response->assertSee('_redirect_to_materials=1', false);
        $response->assertSee('global-open-modal');
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

    public function test_material_create_normalizes_grouped_integer_and_decimal_payloads(): void
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
                    'id' => 56,
                    'family' => 'brick',
                    'label' => 'Brick Numeric Tempel',
                    'brand' => 'Brick Numeric',
                    'type' => 'Tempel',
                ],
            ], 201),
        ]);

        $response = $this->actingAs($user)->post('/materials', [
            'family' => 'brick',
            'brand' => 'Brick Numeric',
            'type' => 'Tempel',
            'form' => 'Persegi',
            'dimension_length' => '20,5',
            'dimension_width' => '10',
            'dimension_height' => '5',
            'price_per_piece' => '35.000',
        ]);

        $response->assertRedirect('/materials?family=brick');
        $response->assertSessionHas('success');

        Http::assertSent(function (ClientRequest $request) {
            return $request->url() === 'http://supply-be.test/api/v1/materials/brick'
                && data_get($request->data(), 'price_per_piece') === 35000.0
                && data_get($request->data(), 'dimension_length') === 20.5;
        });
    }

    public function test_materials_index_shows_recycle_bin_button_when_permission_snapshot_is_empty(): void
    {
        $user = User::factory()->create([
            'permission_snapshot' => [],
        ]);

        Http::fake([
            'http://supply-be.test/api/v1/materials/summary' => Http::response([
                'data' => [
                    'families' => [
                        'brick' => 0,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'nat' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_families' => [
                        'brick' => 0,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'grand_total' => 0,
                ],
            ], 200),
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
            'http://supply-be.test/api/v1/materials/summary' => Http::response([
                'data' => [
                    'families' => [
                        'brick' => 1,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'nat' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'display_families' => [
                        'brick' => 1,
                        'cement' => 0,
                        'sand' => 0,
                        'cat' => 0,
                        'ceramic' => 0,
                        'steel' => 0,
                        'kasa_gypsum' => 0,
                        'paku_tembak' => 0,
                        'paku' => 0,
                    ],
                    'grand_total' => 1,
                ],
            ], 200),
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
