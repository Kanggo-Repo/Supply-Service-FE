<?php

namespace App\Services\Supply;

use App\Models\User;
use App\Support\Auth\SupplyPermissionGate;
use App\Support\Supply\SupplyMaterialCatalog;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupplyServiceClient
{
    public function __construct(
        private readonly SupplyPermissionGate $permissionGate,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function referenceMaterials(?User $user): array
    {
        $payload = $this->get('api/v1/reference/materials', $user);

        return $this->arrayData($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function stores(?User $user): array
    {
        return $this->get('api/v1/stores?perPage=12', $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listStores(array $filters, ?User $user): array
    {
        $query = http_build_query(array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== ''), '', '&', PHP_QUERY_RFC3986);

        return $this->get('api/v1/stores'.($query !== '' ? '?'.$query : ''), $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function showStore(int $id, ?User $user): array
    {
        return $this->get("api/v1/stores/{$id}", $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createStore(array $payload, ?User $user): array
    {
        return $this->write('POST', 'api/v1/stores', $payload, $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateStore(int $id, array $payload, ?User $user): array
    {
        return $this->write('PUT', "api/v1/stores/{$id}", $payload, $user);
    }

    public function deleteStore(int $id, ?User $user): void
    {
        $this->write('DELETE', "api/v1/stores/{$id}", [], $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function showStoreLocation(int $storeId, int $locationId, ?User $user): array
    {
        return $this->get("api/v1/stores/{$storeId}/locations/{$locationId}", $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createStoreLocation(int $storeId, array $payload, ?User $user): array
    {
        return $this->write('POST', "api/v1/stores/{$storeId}/locations", $payload, $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateStoreLocation(int $storeId, int $locationId, array $payload, ?User $user): array
    {
        return $this->write('PUT', "api/v1/stores/{$storeId}/locations/{$locationId}", $payload, $user);
    }

    public function deleteStoreLocation(int $storeId, int $locationId, ?User $user): void
    {
        $this->write('DELETE', "api/v1/stores/{$storeId}/locations/{$locationId}", [], $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function units(?User $user): array
    {
        return $this->get('api/v1/units/grouped', $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listMaterials(string $family, array $filters, ?User $user): array
    {
        $query = http_build_query(array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== ''), '', '&', PHP_QUERY_RFC3986);

        return $this->get('api/v1/materials/'.$family.($query !== '' ? '?'.$query : ''), $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function materialFilterMetadata(string $family, ?User $user): array
    {
        $query = http_build_query([
            'family' => $family,
            'fields' => SupplyMaterialCatalog::suggestionFields($family),
        ], '', '&', PHP_QUERY_RFC3986);

        return $this->get('api/v1/reference/materials/filter-metadata?'.$query, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function showMaterial(string $family, int $id, ?User $user): array
    {
        return $this->get("api/v1/materials/{$family}/{$id}", $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMaterial(string $family, array $payload, ?User $user): array
    {
        return $this->write('POST', "api/v1/materials/{$family}", $payload, $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateMaterial(string $family, int $id, array $payload, ?User $user): array
    {
        return $this->write('PUT', "api/v1/materials/{$family}/{$id}", $payload, $user);
    }

    public function deleteMaterial(string $family, int $id, ?User $user): void
    {
        $this->write('DELETE', "api/v1/materials/{$family}/{$id}", [], $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function materialHistory(string $family, int $id, ?User $user): array
    {
        return $this->get("api/v1/materials/{$family}/{$id}/history", $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function restoreMaterialHistory(string $family, int $id, int $historyLogId, array $payload, ?User $user): array
    {
        return $this->write('POST', "api/v1/materials/{$family}/{$id}/history/{$historyLogId}/restore", $payload, $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function listUnits(array $filters, ?User $user): array
    {
        $query = http_build_query(array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== ''), '', '&', PHP_QUERY_RFC3986);

        return $this->get('api/v1/units'.($query !== '' ? '?'.$query : ''), $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function materialTypes(?User $user): array
    {
        return $this->get('api/v1/units/material-types', $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function showUnit(int $id, ?User $user): array
    {
        return $this->get("api/v1/units/{$id}", $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createUnit(array $payload, ?User $user): array
    {
        return $this->write('POST', 'api/v1/units', $payload, $user);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateUnit(int $id, array $payload, ?User $user): array
    {
        return $this->write('PUT', "api/v1/units/{$id}", $payload, $user);
    }

    public function deleteUnit(int $id, ?User $user): void
    {
        $this->write('DELETE', "api/v1/units/{$id}", [], $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function allStores(array $filters, ?User $user): array
    {
        $query = http_build_query(array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== ''), '', '&', PHP_QUERY_RFC3986);

        return $this->get('api/v1/store-search/all-stores'.($query !== '' ? '?'.$query : ''), $user);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function addressesByStore(array $filters, ?User $user): array
    {
        $query = http_build_query(array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== ''), '', '&', PHP_QUERY_RFC3986);

        return $this->get('api/v1/store-search/addresses-by-store'.($query !== '' ? '?'.$query : ''), $user);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path, ?User $user): array
    {
        return $this->parseResponse($this->request($user)->get($this->url($path)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function arrayData(array $payload): array
    {
        $data = $payload['data'] ?? [];

        return is_array($data) ? array_values($data) : [];
    }

    private function request(?User $user): PendingRequest
    {
        return Http::timeout(15)
            ->connectTimeout(10)
            ->acceptJson()
            ->asJson()
            ->withHeaders($this->headers($user))
            ->withOptions(['verify' => (bool) config('services.supply_service.verify_ssl', true)]);
    }

    /**
     * @return array<string, string>
     */
    private function headers(?User $user): array
    {
        $headers = [
            'X-Service-Name' => (string) config('services.supply_service.service_name', 'supply-fe'),
            'X-Service-Token' => (string) config('services.supply_service.token', ''),
        ];

        if (! $user) {
            return $headers;
        }

        $headers['X-Actor-Name'] = (string) $user->name;
        $headers['X-Actor-Email'] = (string) $user->email;
        $headers['X-Actor-Auth-Provider'] = (string) ($user->auth_provider ?? '');
        $headers['X-Actor-Auth-Subject'] = (string) ($user->auth_subject ?? '');

        $roles = $this->permissionGate->roles($user);
        if ($roles !== []) {
            $headers['X-Actor-Roles'] = implode(',', $roles);
        }

        $permissions = $this->permissionGate->permissions($user);
        if ($permissions !== []) {
            $headers['X-Actor-Permissions'] = implode(',', $permissions);
        }

        return $headers;
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) config('services.supply_service.base_url', ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Supply service base URL is not configured.');
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function write(string $method, string $path, array $payload, ?User $user): array
    {
        $response = match ($method) {
            'POST' => $this->request($user)->post($this->url($path), $payload),
            'PUT' => $this->request($user)->put($this->url($path), $payload),
            'DELETE' => $this->request($user)->delete($this->url($path), $payload),
            default => throw new RuntimeException('Unsupported supply service method.'),
        };

        return $this->parseResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(Response $response): array
    {
        $payload = $response->json();

        if ($response->status() === 422) {
            throw new SupplyServiceValidationException(
                is_array($payload) ? (string) ($payload['message'] ?? 'Supply service validation failed.') : 'Supply service validation failed.',
                is_array($payload['errors'] ?? null) ? $payload['errors'] : [],
            );
        }

        if (! $response->successful()) {
            $message = is_array($payload)
                ? (string) ($payload['message'] ?? $payload['error'] ?? 'Supply service request failed.')
                : 'Supply service request failed.';

            throw new RuntimeException($message);
        }

        return is_array($payload) ? $payload : [];
    }
}
