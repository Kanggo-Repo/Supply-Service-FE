<?php

namespace App\Http\Controllers;

use App\Services\Supply\SupplyServiceClient;
use App\Services\Supply\SupplyServiceValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class UnitManagementController extends Controller
{
    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    public function index(Request $request): View
    {
        $units = collect();
        $typesPayload = ['data' => []];
        $error = null;

        try {
            $payload = $this->supplyServiceClient->listUnits([
                'all' => 1,
                'material_type' => $request->query('material_type'),
                'sort_by' => $request->query('sort_by'),
                'sort_direction' => $request->query('sort_direction'),
            ], $request->user());
            $units = $this->toUnits($payload);
            $typesPayload = $this->supplyServiceClient->materialTypes($request->user());
        } catch (Throwable $exception) {
            report($exception);
            $error = $exception->getMessage();
        }

        $materialTypes = collect((array) ($typesPayload['data'] ?? []))
            ->mapWithKeys(fn (array $type): array => [(string) ($type['value'] ?? '') => (string) ($type['label'] ?? '')])
            ->filter(fn (string $label, string $value): bool => $value !== '')
            ->all();

        return view('units.index', [
            'activeNav' => 'units',
            'units' => $units,
            'materialTypes' => $materialTypes,
            'error' => $error,
        ]);
    }

    public function create(Request $request): View
    {
        $typesPayload = $this->supplyServiceClient->materialTypes($request->user());

        return view('units.create', [
            'materialTypes' => $this->materialTypeOptions($typesPayload),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $this->supplyServiceClient->createUnit($this->unitPayload($request), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Gagal menambahkan unit: '.$exception->getMessage());
        }

        return redirect()->route('units.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    public function edit(Request $request, int $id): View
    {
        $unitPayload = $this->supplyServiceClient->showUnit($id, $request->user());
        $typesPayload = $this->supplyServiceClient->materialTypes($request->user());
        $unit = $this->hydrateUnit((array) ($unitPayload['data'] ?? []));

        return view('units.edit', [
            'unit' => $unit,
            'materialTypes' => $this->materialTypeOptions($typesPayload),
            'selectedTypes' => $unit->materialTypes
                ->pluck('material_type')
                ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                ->values()
                ->all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            $this->supplyServiceClient->updateUnit($id, $this->unitPayload($request), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Gagal mengubah unit: '.$exception->getMessage());
        }

        return redirect()->route('units.index')->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        try {
            $this->supplyServiceClient->deleteUnit($id, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('units.index')->with('error', 'Gagal menghapus unit: '.$exception->getMessage());
        }

        return redirect()->route('units.index')->with('success', 'Unit berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function unitPayload(Request $request): array
    {
        return [
            'code' => trim((string) $request->input('code')),
            'name' => trim((string) $request->input('name')),
            'package_weight' => is_numeric($request->input('package_weight')) ? (float) $request->input('package_weight') : $request->input('package_weight'),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'material_types' => array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                (array) $request->input('material_types', []),
            ))),
        ];
    }

    private function toUnits(array $payload): Collection
    {
        return collect((array) ($payload['data'] ?? []))
            ->map(fn (array $unit): object => $this->hydrateUnit($unit))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function materialTypeOptions(array $payload): array
    {
        return collect((array) ($payload['data'] ?? []))
            ->mapWithKeys(fn (array $type): array => [(string) ($type['value'] ?? '') => (string) ($type['label'] ?? '')])
            ->filter(fn (string $label, string $value): bool => $value !== '')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $unit
     */
    private function hydrateUnit(array $unit): object
    {
        $materialTypes = collect((array) ($unit['material_types'] ?? []))
            ->map(fn (mixed $materialType): object => (object) [
                'material_type' => trim((string) $materialType),
            ])
            ->filter(fn (object $materialType): bool => $materialType->material_type !== '')
            ->values();

        return (object) [
            'id' => (int) ($unit['id'] ?? 0),
            'code' => (string) ($unit['code'] ?? ''),
            'name' => (string) ($unit['name'] ?? ''),
            'package_weight' => $unit['package_weight'] ?? null,
            'description' => $unit['description'] ?? null,
            'materialTypes' => $materialTypes,
        ];
    }
}
