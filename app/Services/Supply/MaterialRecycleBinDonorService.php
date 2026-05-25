<?php

namespace App\Services\Supply;

use App\Models\User;
use App\Support\Supply\SupplyMaterialCatalog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MaterialRecycleBinDonorService
{
    /**
     * @var array<string, array<int, array<string, mixed>>>|null
     */
    private ?array $groupedUnitsCache = null;

    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    /**
     * @return array{
     *     materialTypes: array<string, string>,
     *     materialSummary: Collection<string, int>,
     *     deletedMaterials: Collection<int, object>,
     *     groupedMaterials: Collection<string, Collection<int, object>>,
     *     activeType: string|null
     * }
     */
    public function buildPageData(?User $user, ?string $activeType = null): array
    {
        $payload = $this->supplyServiceClient->materialRecycleBin($user);
        $summary = collect((array) data_get($payload, 'data.summary', []))
            ->map(fn (mixed $count): int => (int) $count);
        $grouped = collect();
        $deletedMaterials = collect();

        foreach ($this->orderedMaterialTypes() as $typeKey => $label) {
            $items = collect((array) data_get($payload, 'data.items', []))
                ->filter(fn (array $item): bool => (string) ($item['material_type'] ?? $item['family'] ?? '') === $typeKey)
                ->map(fn (array $item): object => $this->hydrateDeletedMaterial($typeKey, $item, $user))
                ->sortByDesc(fn (object $item): int => $item->deleted_at instanceof Carbon ? $item->deleted_at->getTimestamp() : 0)
                ->values();

            if ($items->isEmpty()) {
                continue;
            }

            $grouped->put($typeKey, $items);
            $deletedMaterials = $deletedMaterials->concat($items);
            if (! $summary->has($typeKey)) {
                $summary->put($typeKey, $items->count());
            }
        }

        $materialTypes = collect($this->orderedMaterialTypes())
            ->only($grouped->keys()->all())
            ->all();

        $resolvedActiveType = strtolower(trim((string) $activeType));
        if (! array_key_exists($resolvedActiveType, $materialTypes)) {
            $resolvedActiveType = array_key_first($materialTypes) ?: null;
        }

        return [
            'materialTypes' => $materialTypes,
            'materialSummary' => $summary,
            'deletedMaterials' => $deletedMaterials
                ->sortByDesc(fn (object $item): int => $item->deleted_at instanceof Carbon ? $item->deleted_at->getTimestamp() : 0)
                ->values(),
            'groupedMaterials' => $grouped,
            'activeType' => $resolvedActiveType,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDeletedItem(string $type, int $id, ?User $user): ?array
    {
        $payload = $this->supplyServiceClient->materialRecycleBin($user);

        $item = collect((array) data_get($payload, 'data.items', []))
            ->first(function (array $row) use ($type, $id): bool {
                $rowType = (string) ($row['material_type'] ?? $row['family'] ?? '');

                return $rowType === $type && (int) ($row['id'] ?? 0) === $id;
            });

        return is_array($item) ? $item : null;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function hydrateDeletedMaterial(string $type, array $item, ?User $user): object
    {
        $defaultFields = collect(SupplyMaterialCatalog::families())
            ->keys()
            ->flatMap(fn (string $family): array => SupplyMaterialCatalog::writableFields($family))
            ->merge([
                'id',
                'family',
                'material_type',
                'row_material_type',
                'material_kind',
                'label',
                'deleted_by_name',
                'deleted_at_formatted',
                'deleted_by',
            ])
            ->unique()
            ->mapWithKeys(fn (string $field): array => [$field => null])
            ->all();

        $attributes = array_replace($defaultFields, $item, [
            'id' => (int) ($item['id'] ?? 0),
            'family' => $item['family'] ?? $type,
            'material_type' => $item['material_type'] ?? $type,
            'material_kind' => $item['material_kind'] ?? $type,
            'row_material_type' => $item['row_material_type'] ?? $type,
            'label' => $item['label'] ?? '',
        ]);

        $deletedBy = is_array($attributes['deleted_by'] ?? null) ? $attributes['deleted_by'] : [];
        $attributes['deletedBy'] = (object) $deletedBy;
        $attributes['deleted_by_name'] = $attributes['deleted_by_name'] ?? ($deletedBy['name'] ?? null);

        $deletedAtString = trim((string) ($attributes['deleted_at'] ?? ''));
        $attributes['deleted_at'] = $deletedAtString !== '' ? Carbon::parse($deletedAtString) : null;
        $attributes['deleted_at_formatted'] = $attributes['deleted_at_formatted']
            ?? ($attributes['deleted_at'] instanceof Carbon ? $attributes['deleted_at']->format('d-m-Y H:i:s') : null);

        $packageUnitCode = trim((string) ($attributes['package_unit'] ?? ''));
        if ($packageUnitCode !== '') {
            $attributes['packageUnit'] = $this->resolvePackageUnit((string) $attributes['material_type'], $packageUnitCode, $user);
        }

        return (object) $attributes;
    }

    private function resolvePackageUnit(string $family, string $packageUnitCode, ?User $user): object
    {
        $groupedUnits = $this->groupedUnits($user);

        $familyUnits = collect(match ($family) {
            'cement', 'nat' => array_merge((array) ($groupedUnits['cement'] ?? []), (array) ($groupedUnits['nat'] ?? [])),
            default => (array) ($groupedUnits[$family] ?? []),
        });

        $resolved = $familyUnits->first(function (array $unit) use ($packageUnitCode): bool {
            return strtolower((string) ($unit['code'] ?? '')) === strtolower($packageUnitCode);
        });

        if (is_array($resolved)) {
            return (object) $resolved;
        }

        return (object) [
            'code' => $packageUnitCode,
            'name' => $packageUnitCode,
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupedUnits(?User $user): array
    {
        if (is_array($this->groupedUnitsCache)) {
            return $this->groupedUnitsCache;
        }

        $payload = $this->supplyServiceClient->units($user);

        $this->groupedUnitsCache = collect((array) ($payload['data'] ?? []))
            ->map(fn (mixed $units): array => is_array($units) ? array_values($units) : [])
            ->all();

        return $this->groupedUnitsCache;
    }

    /**
     * @return array<string, string>
     */
    private function orderedMaterialTypes(): array
    {
        return [
            'brick' => 'Bata',
            'cement' => 'Semen',
            'nat' => 'Nat',
            'sand' => 'Pasir',
            'ceramic' => 'Keramik',
            'cat' => 'Cat',
            'steel' => 'Besi',
            'kasa_gypsum' => 'Kasa Gypsum',
            'paku_tembak' => 'Paku Tembak',
            'paku' => 'Paku',
        ];
    }
}
