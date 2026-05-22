<?php

namespace App\Services\Supply;

use App\Models\Store;
use App\Models\StoreLocation;
use App\Models\User;
use Illuminate\Support\Collection;

class StoreDonorProjectionService
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Store>
     */
    public function listStores(array $filters, ?User $user): Collection
    {
        $payload = $this->supplyServiceClient->listStores($filters, $user);

        return collect((array) ($payload['data'] ?? []))
            ->map(function (mixed $storeRow) use ($user): ?Store {
                $storeId = (int) data_get($storeRow, 'id', 0);

                return $storeId > 0 ? $this->showStore($storeId, $user) : null;
            })
            ->filter()
            ->values();
    }

    public function showStore(int $storeId, ?User $user): Store
    {
        $payload = $this->supplyServiceClient->showStore($storeId, $user);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $store = $this->hydrateStore($data);
        $locations = collect((array) ($data['locations'] ?? []))
            ->map(function (mixed $locationRow) use ($storeId, $user): ?StoreLocation {
                $locationId = (int) data_get($locationRow, 'id', 0);

                return $locationId > 0 ? $this->showStoreLocation($storeId, $locationId, $user) : null;
            })
            ->filter()
            ->values();

        $store->setRelation('locations', $locations);

        return $this->applyStoreSummary($store);
    }

    public function showStoreLocation(int $storeId, int $locationId, ?User $user): StoreLocation
    {
        $payload = $this->supplyServiceClient->showStoreLocation($storeId, $locationId, $user);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return $this->hydrateLocation($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function hydrateStore(array $data): Store
    {
        $store = new Store([
            'id' => (int) ($data['id'] ?? 0),
            'name' => (string) ($data['name'] ?? ''),
            'location_count' => (int) ($data['location_count'] ?? 0),
            'material_availability_count' => (int) ($data['material_availability_count'] ?? 0),
            'resolved_material_count' => (int) ($data['resolved_material_count'] ?? $data['material_availability_count'] ?? 0),
            'resolved_branch_count' => (int) ($data['resolved_branch_count'] ?? $data['location_count'] ?? 0),
            'has_missing_map_coordinates' => (bool) ($data['has_missing_map_coordinates'] ?? false),
            'missing_map_branch_count' => (int) ($data['missing_map_branch_count'] ?? 0),
        ]);
        $store->exists = true;
        $store->setRelation('locations', collect());

        return $store;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function hydrateLocation(array $data): StoreLocation
    {
        $location = new StoreLocation([
            'id' => (int) ($data['id'] ?? 0),
            'store_id' => (int) ($data['store_id'] ?? 0),
            'address' => $data['address'] ?? null,
            'district' => $data['district'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'place_id' => $data['place_id'] ?? null,
            'formatted_address' => $data['formatted_address'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'material_availabilities_count' => (int) ($data['material_availabilities_count'] ?? 0),
            'resolved_material_count' => (int) ($data['resolved_material_count'] ?? $data['material_availabilities_count'] ?? 0),
        ]);
        $location->exists = true;

        return $location;
    }

    private function applyStoreSummary(Store $store): Store
    {
        /** @var Collection<int, StoreLocation> $locations */
        $locations = $store->getRelation('locations');

        $visibleLocations = $locations
            ->groupBy(fn (StoreLocation $location): string => $this->locationDedupKey($location))
            ->map(function (Collection $group): ?StoreLocation {
                /** @var Collection<int, StoreLocation> $group */
                $primaryLocation = $group
                    ->sort(function (StoreLocation $left, StoreLocation $right): int {
                        $scoreCompare = $this->locationQualityScore($right) <=> $this->locationQualityScore($left);

                        if ($scoreCompare !== 0) {
                            return $scoreCompare;
                        }

                        return (int) $left->id <=> (int) $right->id;
                    })
                    ->first();

                if (! $primaryLocation) {
                    return null;
                }

                $primaryLocation->resolved_material_count = (int) $group->sum(
                    fn (StoreLocation $location): int => (int) ($location->material_availabilities_count ?? 0),
                );
                $primaryLocation->deduped_location_ids = $group
                    ->map(fn (StoreLocation $location): int => (int) $location->id)
                    ->values()
                    ->all();
                $primaryLocation->has_missing_map_coordinates = ! $this->locationHasCoordinates($primaryLocation);

                return $primaryLocation;
            })
            ->filter()
            ->values();

        $store->setRelation('locations', $visibleLocations);
        $store->primary_location = $visibleLocations->first();
        $store->resolved_material_count = (int) $visibleLocations->sum('resolved_material_count');
        $store->resolved_branch_count = $visibleLocations->count();
        $store->missing_map_branch_count = (int) $visibleLocations
            ->filter(fn (StoreLocation $location): bool => (bool) ($location->has_missing_map_coordinates ?? false))
            ->count();
        $store->has_missing_map_coordinates = $visibleLocations->isEmpty() || $store->missing_map_branch_count > 0;

        return $store;
    }

    private function locationDedupKey(StoreLocation $location): string
    {
        $placeId = strtolower(trim((string) ($location->place_id ?? '')));
        if ($placeId !== '') {
            return 'place:'.$placeId;
        }

        $resolvedAddress = strtolower(trim((string) ($location->resolved_address ?? '')));
        if ($resolvedAddress !== '') {
            return 'address:'.preg_replace('/\s+/', ' ', $resolvedAddress);
        }

        return 'id:'.(int) $location->id;
    }

    private function locationQualityScore(StoreLocation $location): int
    {
        $score = 0;

        if (filled($location->formatted_address)) {
            $score += 4;
        }
        if (filled($location->address)) {
            $score += 1;
        }
        if (filled($location->city)) {
            $score += 2;
        }
        if (filled($location->province)) {
            $score += 1;
        }
        if ($this->locationHasCoordinates($location)) {
            $score += 3;
        }

        return $score;
    }

    private function locationHasCoordinates(StoreLocation $location): bool
    {
        return is_numeric($location->latitude) && is_numeric($location->longitude);
    }
}
