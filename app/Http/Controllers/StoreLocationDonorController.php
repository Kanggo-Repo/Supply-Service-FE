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

class StoreLocationDonorController extends Controller
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
        private readonly StoreDonorProjectionService $projectionService,
    ) {}

    public function create(Request $request, int $store): View
    {
        return view('store-locations.create', [
            'store' => $this->projectionService->showStore($store, $request->user()),
        ]);
    }

    public function store(Request $request, int $store): RedirectResponse
    {
        try {
            $this->supplyServiceClient->createStoreLocation($store, $this->locationPayload($request), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal menambah lokasi: '.$exception->getMessage())->withInput();
        }

        return redirect()->to($this->resolvePostSubmitRedirectUrl($request, $store))->with('success', 'Lokasi berhasil ditambahkan!');
    }

    public function edit(Request $request, int $store, int $location): View
    {
        return view('store-locations.edit', [
            'store' => $this->projectionService->showStore($store, $request->user()),
            'location' => $this->projectionService->showStoreLocation($store, $location, $request->user()),
        ]);
    }

    public function update(Request $request, int $store, int $location): RedirectResponse
    {
        try {
            $this->supplyServiceClient->updateStoreLocation($store, $location, $this->locationPayload($request), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal update lokasi: '.$exception->getMessage())->withInput();
        }

        return redirect()->to($this->resolvePostSubmitRedirectUrl($request, $store))->with('success', 'Lokasi berhasil diupdate!');
    }

    public function destroy(Request $request, int $store, int $location): RedirectResponse
    {
        try {
            $this->supplyServiceClient->deleteStoreLocation($store, $location, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Gagal menghapus lokasi: '.$exception->getMessage());
        }

        return redirect()->route('stores.show', $store)->with('success', 'Lokasi berhasil dihapus!');
    }

    public function materials(int $store, int $location): RedirectResponse
    {
        return redirect()
            ->route('materials.index')
            ->with('info', 'Halaman material per lokasi sedang disambungkan ke donor store-location.');
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

    private function resolvePostSubmitRedirectUrl(Request $request, int $store): string
    {
        if ($request->filled('_redirect_url')) {
            return (string) $request->input('_redirect_url');
        }

        if ($request->boolean('_redirect_to_materials')) {
            $referer = trim((string) $request->headers->get('referer', ''));
            $materialsIndexUrl = route('materials.index');

            if ($referer !== '' && str_starts_with($referer, $materialsIndexUrl)) {
                return $referer;
            }
        }

        $referer = trim((string) $request->headers->get('referer', ''));
        $storeShowUrl = route('stores.show', $store);
        $materialsIndexUrl = route('materials.index');

        if ($referer !== '' && (str_starts_with($referer, $materialsIndexUrl) || str_starts_with($referer, $storeShowUrl))) {
            return $referer;
        }

        return $storeShowUrl;
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
