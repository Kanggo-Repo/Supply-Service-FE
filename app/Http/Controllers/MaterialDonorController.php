<?php

namespace App\Http\Controllers;

use App\Helpers\NumberHelper;
use App\Services\Supply\SupplyServiceClient;
use App\Services\Supply\SupplyServiceValidationException;
use App\Support\Supply\SupplyMaterialCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class MaterialDonorController extends Controller
{
    /**
     * @var array<string, array{family: string, variable: string, view: string}>
     */
    private const RESOURCE_MAP = [
        'bricks' => ['family' => 'brick', 'variable' => 'brick', 'view' => 'bricks'],
        'cements' => ['family' => 'cement', 'variable' => 'cement', 'view' => 'cements'],
        'nats' => ['family' => 'nat', 'variable' => 'nat', 'view' => 'nats'],
        'sands' => ['family' => 'sand', 'variable' => 'sand', 'view' => 'sands'],
        'cats' => ['family' => 'cat', 'variable' => 'cat', 'view' => 'cats'],
        'ceramics' => ['family' => 'ceramic', 'variable' => 'ceramic', 'view' => 'ceramics'],
        'steels' => ['family' => 'steel', 'variable' => 'steel', 'view' => 'steels'],
        'kasa_gypsums' => ['family' => 'kasa_gypsum', 'variable' => 'kasaGypsum', 'view' => 'kasa_gypsums'],
        'paku_tembaks' => ['family' => 'paku_tembak', 'variable' => 'pakuTembak', 'view' => 'paku_tembaks'],
        'pakus' => ['family' => 'paku', 'variable' => 'paku', 'view' => 'pakus'],
    ];

    public function __construct(
        private readonly SupplyServiceClient $supplyServiceClient,
    ) {}

    public function create(Request $request): View
    {
        $definition = $this->resourceDefinition((string) $request->route('resource'));

        return view($definition['view'].'.create', [
            'units' => $this->unitsForFamily($definition['family'], $request),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $resource = (string) $request->route('resource');
        $definition = $this->resourceDefinition($resource);
        $material = $this->buildDetailMaterial($resource, $definition['family'], $id, $request);

        return view($definition['view'].'.show', [
            $definition['variable'] => $material,
        ]);
    }

    public function edit(Request $request, int $id): View
    {
        $resource = (string) $request->route('resource');
        $definition = $this->resourceDefinition($resource);
        $material = $this->buildDetailMaterial($resource, $definition['family'], $id, $request);

        return view($definition['view'].'.edit', [
            $definition['variable'] => $material,
            'units' => $this->unitsForFamily($definition['family'], $request),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $definition = $this->resourceDefinition((string) $request->route('resource'));
        $family = $definition['family'];

        try {
            $response = $this->supplyServiceClient->createMaterial($family, $this->materialPayload($request, $family), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return $this->validationFailureResponse($request, $exception);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failureResponse($request, 'Gagal menambahkan material: '.$exception->getMessage());
        }

        $material = (array) ($response['data'] ?? []);
        $payload = [
            'success' => true,
            'message' => $response['message'] ?? 'Material berhasil ditambahkan!',
            'redirect_url' => $this->redirectUrlFor($request, $family),
            'new_material' => [
                'type' => $family,
                'id' => (int) ($material['id'] ?? 0),
            ],
        ];

        if ($this->expectsAjaxPayload($request)) {
            return response()->json($payload, 201);
        }

        return redirect()
            ->to($payload['redirect_url'])
            ->with('success', $payload['message'])
            ->with('new_material', $payload['new_material']);
    }

    public function update(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $definition = $this->resourceDefinition((string) $request->route('resource'));
        $family = $definition['family'];

        try {
            $response = $this->supplyServiceClient->updateMaterial($family, $id, $this->materialPayload($request, $family), $request->user());
        } catch (SupplyServiceValidationException $exception) {
            return $this->validationFailureResponse($request, $exception);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failureResponse($request, 'Gagal mengubah material: '.$exception->getMessage());
        }

        $material = (array) ($response['data'] ?? []);
        $payload = [
            'success' => true,
            'message' => $response['message'] ?? 'Material berhasil diupdate!',
            'redirect_url' => $this->redirectUrlFor($request, $family),
            'updated_material' => [
                'type' => $family,
                'id' => (int) ($material['id'] ?? $id),
            ],
        ];

        if ($this->expectsAjaxPayload($request)) {
            return response()->json($payload);
        }

        return redirect()
            ->to($payload['redirect_url'])
            ->with('success', $payload['message'])
            ->with('updated_material', $payload['updated_material']);
    }

    public function restoreHistory(Request $request, int $id, int $historyLog): JsonResponse|RedirectResponse
    {
        $definition = $this->resourceDefinition((string) $request->route('resource'));
        $family = $definition['family'];

        try {
            $response = $this->supplyServiceClient->restoreMaterialHistory($family, $id, $historyLog, [], $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failureResponse($request, 'Gagal memulihkan riwayat material: '.$exception->getMessage());
        }

        $material = (array) ($response['data'] ?? []);
        $changed = (bool) ($response['changed'] ?? true);
        $payload = [
            'success' => true,
            'changed' => $changed,
            'message' => $response['message'] ?? 'Riwayat material berhasil dipulihkan.',
            'redirect_url' => $this->redirectUrlFor($request, $family),
            'updated_material' => [
                'type' => $family,
                'id' => (int) ($material['id'] ?? $id),
            ],
        ];

        if ($this->expectsAjaxPayload($request)) {
            return response()->json($payload);
        }

        return redirect()
            ->to($payload['redirect_url'])
            ->with($changed ? 'success' : 'info', $payload['message'])
            ->with('updated_material', $payload['updated_material']);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $definition = $this->resourceDefinition((string) $request->route('resource'));
        $family = $definition['family'];

        try {
            $this->supplyServiceClient->deleteMaterial($family, $id, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: '.$exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Material berhasil dihapus.',
        ]);
    }

    public function fieldValues(Request $request, string $field): JsonResponse
    {
        $families = $this->familiesFromRequest((string) $request->route('resource'), $request);
        $search = trim((string) $request->query('search', ''));
        $limit = max(1, min((int) $request->query('limit', 20), 100));
        $filters = collect($request->query())
            ->except(['search', 'limit', 'kinds'])
            ->filter(fn (mixed $value) => $value !== null && $value !== '')
            ->map(fn (mixed $value) => is_string($value) ? trim($value) : $value)
            ->all();

        $values = empty($filters)
            ? $this->fieldValuesFromMetadata($families, $field, $search, $limit, $request)
            : $this->fieldValuesFromRows($families, $field, $filters, $search, $limit, $request);

        return response()->json($values);
    }

    public function allStores(Request $request): JsonResponse
    {
        $payload = $this->supplyServiceClient->allStores([
            'search' => $request->query('search'),
        ], $request->user());

        return response()->json(
            collect($this->extractListPayload($payload))
                ->map(fn (mixed $item): ?string => $this->extractSuggestionText($item, ['store', 'name', 'label']))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        );
    }

    public function addressesByStore(Request $request): JsonResponse
    {
        $payload = $this->supplyServiceClient->addressesByStore([
            'store' => $request->query('store'),
            'search' => $request->query('search'),
        ], $request->user());

        return response()->json(
            collect($this->extractListPayload($payload))
                ->map(fn (mixed $item): ?string => $this->extractSuggestionText($item, ['address', 'resolved_address', 'label']))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        );
    }

    public function locationsByStore(Request $request): JsonResponse
    {
        $payload = $this->supplyServiceClient->locationsByStore([
            'store' => $request->query('store'),
            'limit' => $request->query('limit'),
        ], $request->user());

        return response()->json(
            collect($this->extractListPayload($payload))
                ->map(fn (mixed $item): array => is_array($item) ? $item : [])
                ->values()
                ->all(),
        );
    }

    public function quickCreateStoreLocation(Request $request): JsonResponse
    {
        $response = $this->supplyServiceClient->quickCreateStoreLocationResponse([
            'input' => $request->input('input'),
        ], $request->user());

        return response()->json($response->json() ?? [], $response->status());
    }

    /**
     * @return array{family: string, variable: string, view: string}
     */
    private function resourceDefinition(string $resource): array
    {
        $definition = self::RESOURCE_MAP[$resource] ?? null;
        if (! is_array($definition)) {
            abort(404);
        }

        return $definition;
    }

    /**
     * @return list<object>
     */
    private function unitsForFamily(string $family, Request $request): array
    {
        $grouped = $this->groupedUnits($request);
        $familyKeys = match ($family) {
            'cement', 'nat' => ['cement', 'nat'],
            default => [$family],
        };

        return collect($familyKeys)
            ->flatMap(fn (string $familyKey): array => (array) ($grouped[$familyKey] ?? []))
            ->map(function (mixed $unit): object {
                $data = is_array($unit) ? $unit : [];

                return (object) array_replace([
                    'id' => null,
                    'code' => null,
                    'name' => null,
                    'package_weight' => null,
                    'description' => null,
                ], $data);
            })
            ->unique(fn (object $unit): string => strtolower((string) ($unit->code ?? '')))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupedUnits(Request $request): array
    {
        try {
            $payload = $this->supplyServiceClient->units($request->user());
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        return collect((array) ($payload['data'] ?? []))
            ->map(fn (mixed $units): array => is_array($units) ? array_values($units) : [])
            ->all();
    }

    private function buildDetailMaterial(string $resource, string $family, int $id, Request $request): object
    {
        $payload = $this->supplyServiceClient->showMaterial($family, $id, $request->user());
        $historyPayload = $this->supplyServiceClient->materialHistory($family, $id, $request->user());
        $units = $this->groupedUnits($request);

        $item = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $historyEntries = collect((array) ($historyPayload['data'] ?? []))
            ->map(function (mixed $entry): object {
                $data = is_array($entry) ? $entry : [];
                $editedAt = $data['edited_at'] ?? null;

                return (object) [
                    'id' => (int) ($data['id'] ?? 0),
                    'action' => (string) ($data['action'] ?? 'updated'),
                    'changes' => is_array($data['changes'] ?? null) ? $data['changes'] : [],
                    'edited_at' => is_string($editedAt) && $editedAt !== '' ? Carbon::parse($editedAt) : null,
                    'user' => (object) (is_array($data['user'] ?? null) ? $data['user'] : []),
                ];
            })
            ->values();

        $defaultFields = collect(SupplyMaterialCatalog::families())
            ->keys()
            ->flatMap(fn (string $catalogFamily): array => SupplyMaterialCatalog::writableFields($catalogFamily))
            ->merge([
                'id',
                'photo_url',
                'material_kind',
                'material_type',
                'row_material_type',
            ])
            ->unique()
            ->mapWithKeys(fn (string $field): array => [$field => null])
            ->all();

        $attributes = array_replace($defaultFields, $item, [
            'id' => (int) ($item['id'] ?? $id),
            'material_kind' => $item['material_kind'] ?? $family,
            'material_type' => $item['material_type'] ?? $family,
            'row_material_type' => $item['row_material_type'] ?? $family,
            'history_restore_route_name' => $resource.'.history.restore',
            'materialChangeLogs' => $historyEntries,
        ]);

        $packageUnitCode = (string) ($attributes['package_unit'] ?? '');
        $packageUnit = $this->resolvePackageUnit($family, $packageUnitCode, $units);
        if ($packageUnit !== null) {
            $attributes['packageUnit'] = $packageUnit;
        }

        $photoUrl = trim((string) ($attributes['photo_url'] ?? ''));
        if ($photoUrl === '') {
            $photo = trim((string) ($attributes['photo'] ?? ''));
            $attributes['photo_url'] = str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://') ? $photo : null;
        }

        return new class($attributes)
        {
            /**
             * @param  array<string, mixed>  $attributes
             */
            public function __construct(private readonly array $attributes) {}

            public function __get(string $name): mixed
            {
                return $this->attributes[$name] ?? null;
            }

            public function __isset(string $name): bool
            {
                return array_key_exists($name, $this->attributes);
            }
        };
    }

    private function resolvePackageUnit(string $family, string $packageUnitCode, array $units): ?object
    {
        if ($packageUnitCode === '') {
            return null;
        }

        $familyUnits = collect(match ($family) {
            'cement', 'nat' => array_merge((array) ($units['cement'] ?? []), (array) ($units['nat'] ?? [])),
            default => (array) ($units[$family] ?? []),
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

    private function expectsAjaxPayload(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->wantsJson();
    }

    private function validationFailureResponse(Request $request, SupplyServiceValidationException $exception): JsonResponse|RedirectResponse
    {
        if ($this->expectsAjaxPayload($request)) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        return back()->withErrors($exception->errors())->withInput();
    }

    private function failureResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($this->expectsAjaxPayload($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 500);
        }

        return back()->with('error', $message)->withInput();
    }

    private function redirectUrlFor(Request $request, string $family): string
    {
        if ($request->filled('_redirect_url')) {
            return (string) $request->input('_redirect_url');
        }

        return route('materials.index', [
            'tab' => $family === 'nat' ? 'cement' : $family,
        ]);
    }

    /**
     * @return list<string>
     */
    private function familiesFromRequest(string $resource, Request $request): array
    {
        $kinds = trim((string) $request->query('kinds', ''));
        if ($kinds === '') {
            return [$this->resourceDefinition($resource)['family']];
        }

        return collect(explode(',', $kinds))
            ->map(fn (string $kind): string => trim($kind))
            ->filter(fn (string $kind): bool => $kind !== '' && SupplyMaterialCatalog::exists($kind))
            ->values()
            ->all();
    }

    private function normalizeLookupValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return NumberHelper::formatPlain((float) $value);
        }

        return strtolower(trim((string) $value));
    }

    private function stringifyLookupValue(mixed $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return NumberHelper::formatPlain((float) $value);
        }

        return trim((string) $value);
    }

    /**
     * @param  list<string>  $families
     * @return list<string>
     */
    private function fieldValuesFromMetadata(array $families, string $field, string $search, int $limit, Request $request): array
    {
        return collect($families)
            ->flatMap(function (string $family) use ($field, $request): array {
                $payload = $this->supplyServiceClient->materialFilterMetadata($family, [$field], $request->user());

                return collect((array) data_get($payload, "data.fields.{$field}", []))
                    ->map(fn (mixed $value): ?string => $this->stringifyLookupValue($value))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter(function (string $value) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower($value), strtolower($search));
            })
            ->unique()
            ->sort(fn (string $left, string $right) => strnatcasecmp($left, $right))
            ->values()
            ->take($limit)
            ->all();
    }

    /**
     * @param  list<string>  $families
     * @param  array<string, mixed>  $filters
     * @return list<string>
     */
    private function fieldValuesFromRows(array $families, string $field, array $filters, string $search, int $limit, Request $request): array
    {
        return collect($families)
            ->flatMap(function (string $family) use ($request): array {
                $payload = $this->supplyServiceClient->listMaterials($family, [
                    'perPage' => 500,
                ], $request->user());

                return is_array($payload['data'] ?? null) ? array_values($payload['data']) : [];
            })
            ->filter(function (array $item) use ($filters, $field): bool {
                foreach ($filters as $filterField => $filterValue) {
                    if ($filterField === $field) {
                        continue;
                    }

                    if ($this->normalizeLookupValue($item[$filterField] ?? null) !== $this->normalizeLookupValue($filterValue)) {
                        return false;
                    }
                }

                return true;
            })
            ->map(fn (array $item): ?string => $this->stringifyLookupValue($item[$field] ?? null))
            ->filter(fn (?string $value): bool => $value !== null && $value !== '')
            ->filter(function (string $value) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower($value), strtolower($search));
            })
            ->unique()
            ->sort(fn (string $left, string $right) => strnatcasecmp($left, $right))
            ->values()
            ->take($limit)
            ->all();
    }

    private function extractSuggestionText(mixed $item, array $keys): ?string
    {
        if (is_string($item)) {
            $value = trim($item);

            return $value !== '' ? $value : null;
        }

        if (! is_array($item)) {
            return null;
        }

        foreach ($keys as $key) {
            $value = trim((string) ($item[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string|int, mixed>  $payload
     * @return list<mixed>
     */
    private function extractListPayload(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        return is_array($data) ? array_values($data) : [];
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
