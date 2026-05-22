<?php

namespace App\Http\Controllers;

use App\Models\MaterialSetting;
use App\Services\Supply\SupplyServiceClient;
use App\Services\Supply\SupplyServiceValidationException;
use App\Support\Supply\SupplyMaterialCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MaterialManagementController extends Controller
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    public function index(Request $request): View
    {
        $allSettings = $this->getDisplayMaterialSettings();
        $materials = [];
        $grandTotal = 0;
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

                $dbCount = (int) ($payload['total'] ?? 0);
                $grandTotal += $dbCount;

                $materials[] = [
                    'type' => $type,
                    'label' => MaterialSetting::getMaterialLabel($type),
                    'data' => $this->hydrateMaterialCollection($type, (array) ($payload['data'] ?? [])),
                    'count' => count((array) ($payload['data'] ?? [])),
                    'db_count' => $dbCount,
                    'active_letters' => $isLoaded ? $this->getDisplayActiveLetters((array) ($payload['data'] ?? [])) : [],
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

        $material = [
            'type' => $displayType,
            'label' => MaterialSetting::getMaterialLabel($displayType),
            'data' => $this->hydrateMaterialCollection($displayType, (array) ($payload['data'] ?? [])),
            'count' => count((array) ($payload['data'] ?? [])),
            'db_count' => (int) ($payload['total'] ?? 0),
            'active_letters' => $this->getDisplayActiveLetters((array) ($payload['data'] ?? [])),
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
     * @return array<string, mixed>
     */
    private function getDisplayMaterialPayload(string $type, Request $request): array
    {
        return $this->supplyServiceClient->listMaterials($type, [
            'search' => $request->query('search'),
            'sortBy' => $this->mapSortField((string) $request->query('sort_by', '')),
            'sortDirection' => $request->query('sort_direction'),
            'perPage' => 100,
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
                'map_warning_action_url',
                'map_warning_action_mode',
            ])
            ->unique()
            ->mapWithKeys(fn (string $field): array => [$field => null])
            ->all();

        return (object) array_replace($defaultFields, $item, [
            'id' => (int) ($item['id'] ?? 0),
            'label' => $label,
            'material_kind' => $item['material_kind'] ?? $type,
            'material_type' => $item['material_type'] ?? $type,
            'row_material_type' => $item['row_material_type'] ?? $type,
            'type' => (string) ($item['type'] ?? ''),
            'brand' => (string) ($item['brand'] ?? ''),
            $nameField => $item[$nameField] ?? $label,
        ]);
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
        if (! is_numeric($value) || ! in_array($type, ['number', 'decimal'], true)) {
            return $value;
        }

        return $type === 'number' ? (int) $value : (float) $value;
    }
}
