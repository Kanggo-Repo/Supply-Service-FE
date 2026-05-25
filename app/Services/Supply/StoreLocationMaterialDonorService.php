<?php

namespace App\Services\Supply;

use App\Models\MaterialSetting;
use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\User;
use App\Support\Supply\SupplyMaterialCatalog;
use Illuminate\Support\Collection;

class StoreLocationMaterialDonorService
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
        private readonly StoreDonorProjectionService $projectionService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     store: Store,
     *     location: StoreLocation,
     *     materials: list<array{
     *         type: string,
     *         label: string,
     *         count: int,
     *         db_count: int,
     *         data: Collection<int, object>,
     *         active_letters: list<string>
     *     }>,
     *     allSettings: Collection<int, MaterialSetting>
     * }
     */
    public function buildPageData(int $storeId, int $locationId, array $filters, ?User $user): array
    {
        $store = $this->projectionService->showStore($storeId, $user);
        $location = $this->projectionService->showStoreLocation($storeId, $locationId, $user);
        $payload = $this->supplyServiceClient->storeLocationMaterials($storeId, $locationId, [], $user);

        $rawGroups = collect((array) ($payload['data'] ?? []))
            ->map(fn (mixed $group): array => is_array($group) ? $group : [])
            ->values();

        $visibleTypes = $this->visibleDisplayMaterialTypes($rawGroups);
        $allSettings = $this->getDisplayMaterialSettings($visibleTypes);
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = $this->normalizeSortBy($filters['sort_by'] ?? null);
        $sortDirection = $this->normalizeSortDirection($filters['sort_direction'] ?? null);

        $materials = $allSettings
            ->map(function (MaterialSetting $setting) use ($rawGroups, $search, $sortBy, $sortDirection): array {
                $displayType = $this->normalizeDisplayMaterialType((string) $setting->material_type);

                $rawItems = $displayType === 'cement'
                    ? $this->mergedCementItems($rawGroups)
                    : $this->itemsForType($rawGroups, $displayType);

                $collection = $this->hydrateMaterialCollectionForDisplay($displayType, $rawItems);

                if ($search !== '') {
                    $collection = $this->filterMaterialCollection($collection, $search);
                }

                $collection = $displayType === 'cement'
                    ? $this->sortMergedStoreLocationCementRows($collection, $sortBy, $sortDirection)
                    : $this->sortMaterialsCollection($collection, $displayType, $sortBy, $sortDirection);

                return [
                    'type' => $displayType,
                    'label' => MaterialSetting::getMaterialLabel($displayType),
                    'count' => $collection->count(),
                    'db_count' => $collection->count(),
                    'data' => $collection,
                    'active_letters' => $this->getActiveLetters($collection),
                ];
            })
            ->values()
            ->all();

        return [
            'store' => $store,
            'location' => $location,
            'materials' => $materials,
            'allSettings' => $allSettings,
        ];
    }

    /**
     * @param  Collection<int, object>  $collection
     * @return list<string>
     */
    private function getActiveLetters(Collection $collection): array
    {
        return $collection
            ->map(function (object $item): string {
                $candidate = trim((string) ($item->brand ?? $item->type ?? $item->label ?? '#'));
                $firstChar = strtoupper(substr($candidate, 0, 1));

                return preg_match('/[A-Z]/', $firstChar) === 1 ? $firstChar : '';
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function normalizeDisplayMaterialType(string $type): string
    {
        return $type === 'nat' ? 'cement' : $type;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rawGroups
     * @return list<string>
     */
    private function visibleDisplayMaterialTypes(Collection $rawGroups): array
    {
        return $rawGroups
            ->filter(function (array $group): bool {
                return is_array($group['items'] ?? null) && count((array) $group['items']) > 0;
            })
            ->map(fn (array $group): string => $this->normalizeDisplayMaterialType((string) ($group['type'] ?? '')))
            ->filter(fn (string $type): bool => $type !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, MaterialSetting>
     */
    private function getDisplayMaterialSettings(array $visibleTypes): Collection
    {
        return collect(SupplyMaterialCatalog::families())
            ->keys()
            ->reject(fn (string $type): bool => $type === 'nat')
            ->filter(fn (string $type): bool => in_array($type, $visibleTypes, true))
            ->map(fn (string $type): MaterialSetting => new MaterialSetting([
                'material_type' => $type,
                'is_visible' => true,
            ]))
            ->sortBy(fn (MaterialSetting $setting): string => MaterialSetting::getMaterialLabel((string) $setting->material_type))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rawGroups
     * @return list<array<string, mixed>>
     */
    private function itemsForType(Collection $rawGroups, string $type): array
    {
        $group = $rawGroups->first(fn (array $row): bool => $this->normalizeDisplayMaterialType((string) ($row['type'] ?? '')) === $type);

        return is_array($group['items'] ?? null) ? array_values($group['items']) : [];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rawGroups
     * @return list<array<string, mixed>>
     */
    private function mergedCementItems(Collection $rawGroups): array
    {
        return $rawGroups
            ->filter(function (array $group): bool {
                $type = (string) ($group['type'] ?? '');

                return in_array($type, ['cement', 'nat'], true);
            })
            ->flatMap(function (array $group): array {
                $family = (string) ($group['type'] ?? 'cement');
                $items = is_array($group['items'] ?? null) ? array_values($group['items']) : [];

                return array_map(function (array $item) use ($family): array {
                    $item['family'] = $item['family'] ?? $family;
                    $item['material_kind'] = $item['material_kind'] ?? $family;
                    $item['row_material_type'] = $item['row_material_type'] ?? $family;
                    $item['material_type'] = $item['material_type'] ?? 'cement';

                    return $item;
                }, $items);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return Collection<int, object>
     */
    private function hydrateMaterialCollectionForDisplay(string $displayType, array $items): Collection
    {
        return collect($items)
            ->map(function (array $item) use ($displayType): object {
                $sourceFamily = (string) ($item['family'] ?? $item['material_kind'] ?? $displayType);
                $sourceFamily = SupplyMaterialCatalog::exists($sourceFamily) ? $sourceFamily : $displayType;

                return $this->hydrateMaterialItem($sourceFamily, $displayType, $item);
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hydrateMaterialItem(string $sourceFamily, string $displayType, array $item): object
    {
        $nameField = SupplyMaterialCatalog::nameField($sourceFamily);
        $label = trim((string) ($item['label'] ?? $item[$nameField] ?? $item['brand'] ?? $item['type'] ?? ''));
        $defaultFields = collect(SupplyMaterialCatalog::families())
            ->keys()
            ->flatMap(fn (string $family): array => SupplyMaterialCatalog::writableFields($family))
            ->merge([
                'id',
                'label',
                'photo_url',
                'material_kind',
                'material_type',
                'row_material_type',
            ])
            ->unique()
            ->mapWithKeys(fn (string $field): array => [$field => null])
            ->all();

        return (object) array_replace($defaultFields, $item, [
            'id' => (int) ($item['id'] ?? 0),
            'label' => $label,
            'material_kind' => $item['material_kind'] ?? $sourceFamily,
            'material_type' => $item['material_type'] ?? $displayType,
            'row_material_type' => $item['row_material_type'] ?? $sourceFamily,
            'type' => (string) ($item['type'] ?? ''),
            'brand' => (string) ($item['brand'] ?? ''),
            $nameField => $item[$nameField] ?? $label,
        ]);
    }

    /**
     * @param  Collection<int, object>  $collection
     * @return Collection<int, object>
     */
    private function filterMaterialCollection(Collection $collection, string $search): Collection
    {
        $searchLower = strtolower($search);

        return $collection
            ->filter(function (object $item) use ($searchLower): bool {
                foreach (get_object_vars($item) as $value) {
                    if ($value === null || is_array($value) || is_object($value)) {
                        continue;
                    }

                    if (str_contains(strtolower((string) $value), $searchLower)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    private function normalizeSortBy(mixed $value): ?string
    {
        $sortBy = trim((string) $value);

        return $sortBy !== '' ? $sortBy : null;
    }

    private function normalizeSortDirection(mixed $value): string
    {
        return strtolower((string) $value) === 'desc' ? 'desc' : 'asc';
    }

    /**
     * @param  Collection<int, object>  $collection
     * @return Collection<int, object>
     */
    private function sortMaterialsCollection(Collection $collection, string $materialType, ?string $sortBy, string $sortDirection): Collection
    {
        $priorityColumns = $this->getMaterialSortPriorityColumns($materialType);
        if ($priorityColumns === []) {
            return $collection->values();
        }

        $normalizedDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $primaryColumns = [];

        if ($sortBy && in_array($sortBy, $priorityColumns, true)) {
            if ($materialType === 'ceramic' && $sortBy === 'dimension_length') {
                $primaryColumns = ['dimension_length', 'dimension_width', 'dimension_thickness'];
            } elseif ($materialType === 'steel' && $sortBy === 'dimension_length') {
                $primaryColumns = ['dimension_length', 'dimension_width', 'dimension_height', 'dimension_thickness'];
            } elseif ($materialType === 'kasa_gypsum' && $sortBy === 'dimension_length') {
                $primaryColumns = ['dimension_length', 'dimension_width'];
            } elseif ($materialType === 'paku' && in_array($sortBy, ['dimension_length', 'dimension_length_mm'], true)) {
                $primaryColumns = ['dimension_length', 'dimension_length_mm', 'dimension_body_diameter', 'dimension_head_diameter'];
            } elseif (in_array($materialType, ['brick', 'sand'], true) && $sortBy === 'dimension_length') {
                $primaryColumns = ['dimension_length', 'dimension_width', 'dimension_height'];
            } else {
                $primaryColumns = [$sortBy];
            }
        }

        $sortPlan = [];
        if ($primaryColumns === []) {
            foreach ($priorityColumns as $column) {
                $sortPlan[] = ['column' => $column, 'direction' => 'asc'];
            }
        } else {
            foreach ($primaryColumns as $column) {
                $sortPlan[] = ['column' => $column, 'direction' => $normalizedDirection];
            }
            foreach ($priorityColumns as $column) {
                if (! in_array($column, $primaryColumns, true)) {
                    $sortPlan[] = ['column' => $column, 'direction' => 'asc'];
                }
            }
        }

        return $collection
            ->sort(function (object $left, object $right) use ($sortPlan, $materialType): int {
                foreach ($sortPlan as $rule) {
                    $leftValue = $this->readMaterialSortValue($left, $rule['column'], $materialType);
                    $rightValue = $this->readMaterialSortValue($right, $rule['column'], $materialType);
                    $comparison = $this->compareMaterialSortValues($leftValue, $rightValue);

                    if ($comparison !== 0) {
                        return $rule['direction'] === 'desc' ? -$comparison : $comparison;
                    }
                }

                return ((int) ($left->id ?? 0)) <=> ((int) ($right->id ?? 0));
            })
            ->values();
    }

    /**
     * @param  Collection<int, object>  $collection
     * @return Collection<int, object>
     */
    private function sortMergedStoreLocationCementRows(Collection $collection, ?string $sortBy, string $sortDirection): Collection
    {
        $priorityColumns = $this->getMaterialSortPriorityColumns('cement');
        $normalizedDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';
        $primaryColumns = [];

        if ($sortBy && in_array($sortBy, $priorityColumns, true)) {
            $primaryColumns = [$sortBy];
        }

        $sortPlan = [];
        if ($primaryColumns === []) {
            foreach ($priorityColumns as $column) {
                $sortPlan[] = ['column' => $column, 'direction' => 'asc'];
            }
        } else {
            foreach ($primaryColumns as $column) {
                $sortPlan[] = ['column' => $column, 'direction' => $normalizedDirection];
            }
            foreach ($priorityColumns as $column) {
                if (! in_array($column, $primaryColumns, true)) {
                    $sortPlan[] = ['column' => $column, 'direction' => 'asc'];
                }
            }
        }

        return $collection
            ->sort(function (object $left, object $right) use ($sortPlan): int {
                foreach ($sortPlan as $rule) {
                    $leftValue = $this->readMaterialSortValue($left, $rule['column'], 'cement');
                    $rightValue = $this->readMaterialSortValue($right, $rule['column'], 'cement');
                    $comparison = $this->compareMaterialSortValues($leftValue, $rightValue);

                    if ($comparison !== 0) {
                        return $rule['direction'] === 'desc' ? -$comparison : $comparison;
                    }
                }

                $leftType = (string) ($left->row_material_type ?? 'cement');
                $rightType = (string) ($right->row_material_type ?? 'cement');
                if ($leftType !== $rightType) {
                    return strcmp($leftType, $rightType);
                }

                return ((int) ($left->id ?? 0)) <=> ((int) ($right->id ?? 0));
            })
            ->values();
    }

    /**
     * @return list<string>
     */
    private function getMaterialSortPriorityColumns(string $materialType): array
    {
        return match ($materialType) {
            'brick' => ['type', 'brand', 'form', 'dimension_length', 'dimension_width', 'dimension_height', 'package_volume', 'package_type', 'store', 'address', 'price_per_piece', 'comparison_price_per_m3'],
            'sand' => ['type', 'brand', 'package_unit', 'dimension_length', 'dimension_width', 'dimension_height', 'package_volume', 'store', 'address', 'package_price', 'comparison_price_per_m3'],
            'cat' => ['type', 'brand', 'sub_brand', 'color_code', 'color_name', 'package_unit', 'package_weight_gross', 'volume', 'package_weight_net', 'store', 'address', 'purchase_price', 'comparison_price_per_kg'],
            'cement' => ['type', 'brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net', 'store', 'address', 'package_price', 'comparison_price_per_kg'],
            'ceramic' => ['type', 'brand', 'dimension_length', 'dimension_width', 'dimension_thickness', 'sub_brand', 'surface', 'code', 'color', 'form', 'packaging', 'pieces_per_package', 'coverage_per_package', 'store', 'address', 'price_per_package', 'comparison_price_per_m2'],
            'nat' => ['type', 'nat_name', 'brand', 'sub_brand', 'code', 'color', 'package_unit', 'package_weight_net', 'store', 'address', 'package_price', 'comparison_price_per_kg'],
            'steel' => ['type', 'brand', 'quality', 'term', 'form', 'package_unit', 'dimension_length', 'dimension_width', 'dimension_height', 'dimension_thickness', 'package_volume', 'store', 'address', 'package_price', 'comparison_price_per_m3'],
            'kasa_gypsum' => ['type', 'brand', 'package_unit', 'dimension_width', 'dimension_length', 'store', 'address', 'package_price', 'comparison_price_per_m'],
            'paku_tembak' => ['type', 'brand', 'package_unit', 'mesiu_code', 'mesiu_size', 'mesiu_content', 'paku_code', 'paku_size', 'paku_content', 'store', 'address', 'package_price', 'comparison_price'],
            'paku' => ['type', 'brand', 'dimension_length', 'dimension_length_mm', 'dimension_body_diameter', 'dimension_head_diameter', 'color', 'package_unit', 'package_weight', 'package_content', 'store', 'address', 'package_price', 'comparison_price'],
            default => [],
        };
    }

    private function readMaterialSortValue(object $item, string $column, string $materialType): mixed
    {
        $resolvedColumn = $materialType === 'nat' && $column === 'type' ? 'nat_name' : $column;
        $value = $item->{$resolvedColumn} ?? null;

        return is_string($value) ? trim($value) : $value;
    }

    private function compareMaterialSortValues(mixed $left, mixed $right): int
    {
        $leftIsEmpty = $left === null || $left === '';
        $rightIsEmpty = $right === null || $right === '';

        if ($leftIsEmpty && $rightIsEmpty) {
            return 0;
        }
        if ($leftIsEmpty) {
            return 1;
        }
        if ($rightIsEmpty) {
            return -1;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }

        return strnatcasecmp((string) $left, (string) $right);
    }
}
