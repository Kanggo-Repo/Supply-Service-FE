<?php

namespace App\Http\Controllers;

use App\Models\MaterialSetting;
use App\Services\Supply\SupplyServiceClient;
use App\Services\Supply\SupplyServiceValidationException;
use App\Support\Supply\SupplyMaterialCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MaterialManagementController extends Controller
{
    private const MATERIAL_TAB_CHUNK_SIZE = 50;

    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    public function index(Request $request): View
    {
        $allSettings = $this->getDisplayMaterialSettings();
        $materialSummary = $this->getDisplayMaterialSummary($request);
        $countSummary = (array) ($materialSummary['counts'] ?? []);
        $letterSummary = (array) ($materialSummary['letters'] ?? []);
        $letterPageSummary = (array) ($materialSummary['letter_pages'] ?? []);
        $materials = [];
        $grandTotal = array_sum($countSummary);
        $activeTab = $this->normalizeDisplayMaterialType((string) $request->query('tab', ''));
        $firstType = $this->normalizeDisplayMaterialType((string) ($allSettings->first()->material_type ?? 'brick'));
        $targetTab = $activeTab !== '' ? $activeTab : $firstType;
        $error = null;

        try {
            foreach ($allSettings as $setting) {
                $type = $this->normalizeDisplayMaterialType((string) $setting->material_type);
                $isLoaded = $type === $targetTab;
                $payload = $isLoaded
                    ? $this->getDisplayMaterialPayload($type, $request)
                    : ['data' => [], 'current_page' => 1, 'per_page' => 15, 'total' => 0, 'last_page' => 1];

                $dbCount = (int) ($countSummary[$type] ?? ($payload['total'] ?? 0));

                $materials[] = [
                    'type' => $type,
                    'label' => MaterialSetting::getMaterialLabel($type),
                    'data' => $this->hydrateMaterialCollection($type, (array) ($payload['data'] ?? [])),
                    'count' => count((array) ($payload['data'] ?? [])),
                    'db_count' => $dbCount,
                    'active_letters' => (array) ($letterSummary[$type] ?? ($isLoaded ? $this->getDisplayActiveLetters((array) ($payload['data'] ?? [])) : [])),
                    'letter_pages' => (array) ($letterPageSummary[$type] ?? []),
                    'pagination' => $this->extractPagination($payload),
                    'is_loaded' => $isLoaded,
                ];
            }
        } catch (Throwable $exception) {
            report($exception);
            $error = $exception->getMessage();
        }

        return view('materials.index', [
            'materials' => $materials,
            'allSettings' => $allSettings,
            'grandTotal' => $grandTotal,
            'inlinePackageUnits' => $this->getInlinePackageUnits($request),
            'error' => $error,
        ]);
    }

    public function fetchTab(Request $request, string $type): View
    {
        $displayType = $this->normalizeDisplayMaterialType($type);
        abort_unless($this->isSupportedDisplayMaterialType($displayType), 404);

        $payload = $this->getDisplayMaterialPayload($displayType, $request);
        $materialSummary = $this->getDisplayMaterialSummary($request);
        $letterSummary = (array) ($materialSummary['letters'] ?? []);
        $letterPageSummary = (array) ($materialSummary['letter_pages'] ?? []);

        $material = [
            'type' => $displayType,
            'label' => MaterialSetting::getMaterialLabel($displayType),
            'data' => $this->hydrateMaterialCollection($displayType, (array) ($payload['data'] ?? [])),
            'count' => count((array) ($payload['data'] ?? [])),
            'db_count' => (int) ($payload['total'] ?? 0),
            'active_letters' => (array) ($letterSummary[$displayType] ?? $this->getDisplayActiveLetters((array) ($payload['data'] ?? []))),
            'letter_pages' => (array) ($letterPageSummary[$displayType] ?? []),
            'pagination' => $this->extractPagination($payload),
            'is_loaded' => true,
        ];

        return view('materials.partials.table', [
            'material' => $material,
            'grandTotal' => (int) ($payload['total'] ?? 0),
            'inlinePackageUnits' => $this->getInlinePackageUnits($request),
        ]);
    }

    public function create(Request $request): View
    {
        $family = $this->resolveFamily((string) $request->query('family', 'brick'));

        return view('materials.form', [
            'activeNav' => 'materials',
            'mode' => 'create',
            'family' => $family,
            'familyMeta' => SupplyMaterialCatalog::family($family),
            'families' => SupplyMaterialCatalog::families(),
            'material' => [],
            'fields' => SupplyMaterialCatalog::formFields($family),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $family = $this->resolveFamily((string) $request->input('family', ''));
        $validator = Validator::make($request->all(), [
            'family' => ['required'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $this->supplyServiceClient->createMaterial($family, $this->materialPayload($request, $family), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan material: '.$exception->getMessage());
        }

        return redirect()
            ->route('materials.index', ['family' => $family])
            ->with('success', 'Material berhasil ditambahkan.');
    }

    public function edit(Request $request, string $family, int $id): View
    {
        $family = $this->resolveFamily($family);
        $materialPayload = $this->supplyServiceClient->showMaterial($family, $id, $request->user());

        return view('materials.form', [
            'activeNav' => 'materials',
            'mode' => 'edit',
            'family' => $family,
            'familyMeta' => SupplyMaterialCatalog::family($family),
            'families' => SupplyMaterialCatalog::families(),
            'material' => (array) ($materialPayload['data'] ?? []),
            'fields' => SupplyMaterialCatalog::formFields($family),
        ]);
    }

    public function update(Request $request, string $family, int $id): RedirectResponse
    {
        $family = $this->resolveFamily($family);

        try {
            $this->supplyServiceClient->updateMaterial($family, $id, $this->materialPayload($request, $family), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Gagal mengubah material: '.$exception->getMessage());
        }

        return redirect()
            ->route('materials.index', ['family' => $family])
            ->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy(Request $request, string $family, int $id): RedirectResponse
    {
        $family = $this->resolveFamily($family);

        try {
            $this->supplyServiceClient->deleteMaterial($family, $id, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('materials.index', ['family' => $family])
                ->with('error', 'Gagal menghapus material: '.$exception->getMessage());
        }

        return redirect()
            ->route('materials.index', ['family' => $family])
            ->with('success', 'Material berhasil dihapus.');
    }

    public function typeSuggestions(Request $request): JsonResponse
    {
        $query = strtolower(trim((string) $request->query('q', '')));
        $aliases = [
            'brick' => ['bata', 'brick'],
            'cement' => ['semen', 'cement', 'nat', 'grout'],
            'sand' => ['pasir', 'sand'],
            'cat' => ['cat'],
            'ceramic' => ['keramik', 'ceramic'],
            'steel' => ['besi', 'steel'],
            'kasa_gypsum' => ['kasa', 'kasa gypsum', 'kasa_gypsum'],
            'paku_tembak' => ['paku tembak', 'tembak', 'paku_tembak'],
            'paku' => ['paku', 'paku biasa', 'pakus'],
        ];

        $items = collect(SupplyMaterialCatalog::families())
            ->map(function (array $meta, string $family) use ($aliases): array {
                $label = (string) ($meta['label'] ?? $family);

                return [
                    'material_type' => $family === 'nat' ? 'cement' : $family,
                    'type' => $label,
                    'label' => $label,
                    '_keywords' => array_merge([$family, strtolower($label)], $aliases[$family] ?? []),
                ];
            })
            ->filter(function (array $item) use ($query): bool {
                if ($query === '') {
                    return true;
                }

                foreach ((array) ($item['_keywords'] ?? []) as $keyword) {
                    if (str_contains((string) $keyword, $query)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(fn (array $item): array => Arr::only($item, ['material_type', 'type', 'label']))
            ->values()
            ->all();

        return response()->json([
            'items' => $items,
        ]);
    }

    private function resolveFamily(string $family): string
    {
        abort_unless(SupplyMaterialCatalog::exists($family), 404);

        return $family;
    }

    private function normalizeDisplayMaterialType(string $type): string
    {
        return $type === 'nat' ? 'cement' : $type;
    }

    private function isSupportedDisplayMaterialType(string $type): bool
    {
        return array_key_exists($type, $this->displayMaterialFamilies());
    }

    private function getDisplayMaterialSettings(): Collection
    {
        return collect($this->displayMaterialFamilies())
            ->keys()
            ->values()
            ->map(fn (string $type): MaterialSetting => new MaterialSetting([
                'material_type' => $type,
            ]));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function displayMaterialFamilies(): array
    {
        return collect(SupplyMaterialCatalog::families())
            ->except(['nat'])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function getDisplayMaterialSummary(Request $request): array
    {
        try {
            $summaryPayload = $this->supplyServiceClient->materialSummary($request->user());
            $summary = (array) ($summaryPayload['data']['display_families'] ?? []);
            $letters = collect((array) ($summaryPayload['data']['display_letters'] ?? []))
                ->map(fn (mixed $value): array => is_array($value) ? array_values($value) : [])
                ->all();
            $letterPages = collect((array) ($summaryPayload['data']['display_letter_pages'] ?? []))
                ->map(fn (mixed $value): array => is_array($value) ? $value : [])
                ->all();

            if ($summary !== []) {
                return [
                    'counts' => collect($this->displayMaterialFamilies())
                        ->keys()
                        ->mapWithKeys(fn (string $type): array => [
                            $type => (int) ($summary[$type] ?? 0),
                        ])
                        ->all(),
                    'letters' => collect($this->displayMaterialFamilies())
                        ->keys()
                        ->mapWithKeys(fn (string $type): array => [
                            $type => array_values(array_filter((array) ($letters[$type] ?? []), fn (mixed $letter): bool => is_string($letter) && $letter !== '')),
                        ])
                        ->all(),
                    'letter_pages' => collect($this->displayMaterialFamilies())
                        ->keys()
                        ->mapWithKeys(fn (string $type): array => [
                            $type => array_filter((array) ($letterPages[$type] ?? []), fn (mixed $page, mixed $letter): bool => is_string($letter) && $letter !== '' && is_numeric($page), ARRAY_FILTER_USE_BOTH),
                        ])
                        ->all(),
                ];
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        $counts = [];
        $letters = [];
        $letterPages = [];

        foreach ($this->displaySourceFamilies() as $displayType => $families) {
            $counts[$displayType] = collect($families)
                ->sum(function (string $family) use ($request): int {
                    try {
                        $payload = $this->supplyServiceClient->listMaterials($family, [
                            'perPage' => 1,
                        ], $request->user());
                    } catch (Throwable $exception) {
                        report($exception);

                        return 0;
                    }

                    return (int) ($payload['total'] ?? 0);
                });

            $letters[$displayType] = collect($families)
                ->flatMap(function (string $family) use ($request): array {
                    try {
                        $payload = $this->supplyServiceClient->materialFilterMetadata($family, ['brand'], $request->user());
                    } catch (Throwable $exception) {
                        report($exception);

                        return [];
                    }

                    return (array) data_get($payload, 'data.fields.brand', []);
                })
                ->map(function (mixed $value): string {
                    $brand = trim((string) $value);
                    $firstChar = strtoupper(substr($brand, 0, 1));

                    return preg_match('/^[A-Z]$/', $firstChar) === 1 ? $firstChar : '';
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            $letterPages[$displayType] = collect($letters[$displayType])
                ->values()
                ->mapWithKeys(function (string $letter, int $index): array {
                    return [$letter => (int) floor($index / self::MATERIAL_TAB_CHUNK_SIZE) + 1];
                })
                ->all();
        }

        return [
            'counts' => $counts,
            'letters' => $letters,
            'letter_pages' => $letterPages,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDisplayMaterialPayload(string $type, Request $request): array
    {
        return $this->supplyServiceClient->listMaterials($type, [
            'page' => max(1, (int) $request->query('page', 1)),
            'perPage' => self::MATERIAL_TAB_CHUNK_SIZE,
            'search' => $request->query('search'),
            'sortBy' => $this->mapSortField((string) $request->query('sort_by', '')),
            'sortDirection' => $request->query('sort_direction'),
            'letter' => $request->query('letter'),
        ], $request->user());
    }

    private function mapSortField(string $sortBy): ?string
    {
        return match ($sortBy) {
            'brand', 'type', 'updated_at' => $sortBy,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, int|null>
     */
    private function extractPagination(array $payload): array
    {
        $currentPage = max(1, (int) ($payload['current_page'] ?? 1));
        $lastPage = max(1, (int) ($payload['last_page'] ?? 1));
        $perPage = max(1, (int) ($payload['per_page'] ?? self::MATERIAL_TAB_CHUNK_SIZE));
        $total = max(0, (int) ($payload['total'] ?? 0));

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'next_page' => $currentPage < $lastPage ? $currentPage + 1 : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, object>
     */
    private function hydrateMaterialCollection(string $type, array $items): Collection
    {
        return collect($items)
            ->map(fn (array $item): object => $this->hydrateMaterialItem($type, $item))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hydrateMaterialItem(string $type, array $item): object
    {
        $nameField = SupplyMaterialCatalog::nameField($type);
        $label = trim((string) ($item['label'] ?? $item[$nameField] ?? $item['brand'] ?? ''));
        $defaultFields = collect(SupplyMaterialCatalog::families())
            ->keys()
            ->flatMap(fn (string $family): array => SupplyMaterialCatalog::writableFields($family))
            ->merge([
                'id',
                'label',
                'material_kind',
                'material_type',
                'row_material_type',
                'deleted_by_name',
                'deleted_at_formatted',
                'has_missing_map_coordinates',
                'map_warning_label',
                'map_warning_reason',
                'map_warning_action_context',
                'map_warning_store_id',
                'map_warning_store_location_id',
                'map_warning_action_url',
                'map_warning_action_mode',
            ])
            ->unique()
            ->mapWithKeys(fn (string $field): array => [$field => null])
            ->all();

        $material = (object) array_replace($defaultFields, $item, [
            'id' => (int) ($item['id'] ?? 0),
            'label' => $label,
            'material_kind' => $item['material_kind'] ?? $type,
            'material_type' => $item['material_type'] ?? $type,
            'row_material_type' => $item['row_material_type'] ?? $type,
            'type' => (string) ($item['type'] ?? ''),
            'brand' => (string) ($item['brand'] ?? ''),
            $nameField => $item[$nameField] ?? $label,
        ]);

        $this->applyMapWarningActionMetadata($material);

        return $material;
    }

    private function applyMapWarningActionMetadata(object $material): void
    {
        if (! ($material->has_missing_map_coordinates ?? false)) {
            return;
        }

        $actionContext = trim((string) ($material->map_warning_action_context ?? ''));
        $storeName = trim((string) ($material->store ?? ''));
        $storeId = (int) ($material->map_warning_store_id ?? 0);
        $storeLocationId = (int) ($material->map_warning_store_location_id ?? 0);

        if ($actionContext === 'store-search' && $storeName !== '') {
            $material->map_warning_action_url = route('stores.index', ['search' => $storeName]);
            $material->map_warning_action_mode = 'page';

            return;
        }

        if ($actionContext === 'store-location-edit' && $storeId > 0 && $storeLocationId > 0) {
            $material->map_warning_action_url = route('store-locations.edit', [
                'store' => $storeId,
                'location' => $storeLocationId,
                '_redirect_url' => route('materials.index'),
                '_redirect_to_materials' => 1,
            ]);
            $material->map_warning_action_mode = 'modal';
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return list<string>
     */
    private function getDisplayActiveLetters(array $items): array
    {
        return collect($items)
            ->map(function (array $item): string {
                $candidate = trim((string) ($item['brand'] ?? $item['type'] ?? $item['label'] ?? ''));
                $firstChar = strtoupper(substr($candidate, 0, 1));

                return preg_match('/[A-Z]/', $firstChar) === 1 ? $firstChar : '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getInlinePackageUnits(Request $request): array
    {
        try {
            $payload = $this->supplyServiceClient->units($request->user());
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        $grouped = collect((array) ($payload['data'] ?? []))
            ->map(fn (mixed $units): array => is_array($units) ? array_values($units) : [])
            ->all();

        $cementUnits = array_values(array_merge(
            (array) ($grouped['cement'] ?? []),
            (array) ($grouped['nat'] ?? []),
        ));

        if ($cementUnits !== []) {
            $grouped['cement'] = collect($cementUnits)
                ->unique(fn (array $unit): string => strtolower((string) ($unit['code'] ?? '')))
                ->values()
                ->all();
        }

        return $grouped;
    }

    /**
     * @return array<string, list<string>>
     */
    private function displaySourceFamilies(): array
    {
        return collect($this->displayMaterialFamilies())
            ->keys()
            ->mapWithKeys(fn (string $type): array => [
                $type => $type === 'cement' ? ['cement', 'nat'] : [$type],
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function materialPayload(Request $request, string $family): array
    {
        $fieldDefinitions = SupplyMaterialCatalog::formFields($family);
        $payload = [];

        foreach (array_keys($fieldDefinitions) as $field) {
            if (! $request->exists($field)) {
                continue;
            }

            $value = $request->input($field);
            if (is_string($value)) {
                $value = trim($value);
            }

            $payload[$field] = $value === '' ? null : $this->castNumericIfNeeded($fieldDefinitions, $field, $value);
        }

        return $payload;
    }

    /**
     * @param  array<string, array<string, mixed>>  $fieldDefinitions
     */
    private function castNumericIfNeeded(array $fieldDefinitions, string $field, mixed $value): mixed
    {
        $type = data_get($fieldDefinitions, "{$field}.type");
        if (! in_array($type, ['number', 'decimal'], true)) {
            return $value;
        }

        $normalized = $this->normalizeNumericInput($value);
        if (! is_numeric($normalized)) {
            return $value;
        }

        return $type === 'number' ? (int) $normalized : (float) $normalized;
    }

    private function normalizeNumericInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return $normalized;
        }

        $normalized = preg_replace('/[\s\x{00A0}]+/u', '', $normalized) ?? $normalized;

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma) {
            if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $normalized) === 1) {
                $normalized = str_replace(',', '', $normalized);
            } else {
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif ($hasDot) {
            if (! str_starts_with($normalized, '0.') && ! str_starts_with($normalized, '-0.')
                && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $normalized) === 1) {
                $normalized = str_replace('.', '', $normalized);
            }
        }

        return $normalized;
    }
}
