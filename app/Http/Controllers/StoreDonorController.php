<?php

namespace App\Http\Controllers;

use App\Services\Supply\StoreDonorProjectionService;
use App\Services\Supply\SupplyServiceClient;
use App\Services\Supply\SupplyServiceValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class StoreDonorController extends Controller
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
        private readonly StoreDonorProjectionService $projectionService,
    ) {}

    public function index(Request $request): View
    {
        $stores = collect();

        try {
            $stores = $this->projectionService->listStores([
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by'),
                'sort_direction' => $request->query('sort_direction'),
                'perPage' => 100,
            ], $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return view('workspace.stores', [
                'activeNav' => 'stores',
                'error' => 'Gagal memuat data store dari Supply BE: '.$exception->getMessage(),
                'stores' => [],
                'storeTotal' => 0,
            ]);
        }

        return view('stores.index', [
            'activeNav' => 'stores',
            'stores' => $stores,
        ]);
    }

    public function create(): View
    {
        return view('stores.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $storeId = null;

        try {
            $storeResponse = $this->supplyServiceClient->createStore([
                'name' => trim((string) $request->input('name')),
            ], $request->user());
            $storeId = (int) data_get($storeResponse, 'data.id', 0);

            if ($storeId > 0 && $this->hasInitialLocationData($request)) {
                $this->supplyServiceClient->createStoreLocation($storeId, $this->locationPayload($request), $request->user());
            }
        } catch (SupplyServiceValidationException $exception) {
            $this->rollbackStoreIfNeeded($storeId, $request);

            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            $this->rollbackStoreIfNeeded($storeId, $request);
            report($exception);

            return back()->with('error', 'Gagal menyimpan toko: '.$exception->getMessage())->withInput();
        }

        return redirect()->route('stores.index')->with('success', 'Toko berhasil ditambahkan!');
    }

    public function show(Request $request, int $store): View
    {
        $storeModel = $this->projectionService->showStore($store, $request->user());

        return view('stores.show', [
            'store' => $storeModel,
        ]);
    }

    public function edit(Request $request, int $store): View
    {
        $storeModel = $this->projectionService->showStore($store, $request->user());

        return view('stores.edit', [
            'store' => $storeModel,
        ]);
    }

    public function update(Request $request, int $store): RedirectResponse
    {
        try {
            $this->supplyServiceClient->updateStore($store, [
                'name' => trim((string) $request->input('name')),
            ], $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal update toko: '.$exception->getMessage())->withInput();
        }

        return redirect()->route('stores.index')->with('success', 'Toko berhasil diupdate!');
    }

    public function destroy(Request $request, int $store): RedirectResponse
    {
        try {
            $this->supplyServiceClient->deleteStore($store, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal menghapus toko: '.$exception->getMessage());
        }

        return redirect()->route('stores.index')->with('success', 'Toko berhasil dihapus!');
    }

    private function hasInitialLocationData(Request $request): bool
    {
        return filled($request->input('address'))
            || filled($request->input('district'))
            || filled($request->input('city'))
            || filled($request->input('province'))
            || filled($request->input('latitude'))
            || filled($request->input('longitude'))
            || filled($request->input('place_id'))
            || filled($request->input('formatted_address'))
            || $this->flattenContactColumn($this->normalizeContactPairs(
                $this->normalizeContactField($request->input('contact_name')),
                $this->normalizeContactField($request->input('contact_phone')),
            ), 'name') !== null
            || $this->flattenContactColumn($this->normalizeContactPairs(
                $this->normalizeContactField($request->input('contact_name')),
                $this->normalizeContactField($request->input('contact_phone')),
            ), 'phone') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    private function locationPayload(Request $request): array
    {
        $contactPairs = $this->normalizeContactPairs(
            $this->normalizeContactField($request->input('contact_name')),
            $this->normalizeContactField($request->input('contact_phone')),
        );

        return [
            'address' => $this->trimmedOrNull($request->input('address')),
            'district' => $this->trimmedOrNull($request->input('district')),
            'city' => $this->trimmedOrNull($request->input('city')),
            'province' => $this->trimmedOrNull($request->input('province')),
            'latitude' => is_numeric($request->input('latitude')) ? (float) $request->input('latitude') : null,
            'longitude' => is_numeric($request->input('longitude')) ? (float) $request->input('longitude') : null,
            'place_id' => $this->trimmedOrNull($request->input('place_id')),
            'formatted_address' => $this->trimmedOrNull($request->input('formatted_address')),
            'contact_name' => $this->flattenContactColumn($contactPairs, 'name'),
            'contact_phone' => $this->flattenContactColumn($contactPairs, 'phone'),
        ];
    }

    private function rollbackStoreIfNeeded(?int $storeId, Request $request): void
    {
        if (! $storeId || $storeId <= 0) {
            return;
        }

        try {
            $this->supplyServiceClient->deleteStore($storeId, $request->user());
        } catch (Throwable $rollbackException) {
            report($rollbackException);
        }
    }

    /**
     * @return list<string>
     */
    private function normalizeContactField(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map(static fn (mixed $item): string => trim((string) $item), $value));
        }

        if ($value === null) {
            return [];
        }

        return [trim((string) $value)];
    }

    /**
     * @param  list<string>  $names
     * @param  list<string>  $phones
     * @return Collection<int, array{name: string, phone: string}>
     */
    private function normalizeContactPairs(array $names, array $phones): Collection
    {
        $pairs = collect();
        $count = max(count($names), count($phones));

        for ($i = 0; $i < $count; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $phone = trim((string) ($phones[$i] ?? ''));

            if ($name === '' && $phone === '') {
                continue;
            }

            $pairs->push([
                'name' => $name,
                'phone' => $phone,
            ]);
        }

        return $pairs;
    }

    /**
     * @param  Collection<int, array{name: string, phone: string}>  $pairs
     */
    private function flattenContactColumn(Collection $pairs, string $column): ?string
    {
        if ($pairs->isEmpty()) {
            return null;
        }

        $text = $pairs
            ->map(fn (array $pair): string => $pair[$column] !== '' ? $pair[$column] : '-')
            ->implode(' | ');

        return $text !== '' ? $text : null;
    }

    private function trimmedOrNull(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }
}
