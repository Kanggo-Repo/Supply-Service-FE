<?php

namespace App\Services\Platform;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PlatformServiceClient
{
    public function me(string $accessToken): array
    {
        return $this->request('/api/v1/me', $accessToken);
    }

    public function navigation(string $accessToken): array
    {
        return $this->request('/api/v1/navigation', $accessToken);
    }

    private function request(string $path, string $accessToken, array $query = []): array
    {
        $response = Http::baseUrl((string) config('services.platform_service.base_url'))
            ->acceptJson()
            ->withToken($accessToken)
            ->get($path, $query);

        if ($response->failed()) {
            throw new RuntimeException($this->resolveErrorMessage($response));
        }

        return $response->json('data', []);
    }

    private function resolveErrorMessage(Response $response): string
    {
        $message = trim((string) ($response->json('message') ?? ''));

        if ($message !== '') {
            return $message;
        }

        $errors = $response->json('errors');

        if (is_array($errors)) {
            $flattened = collect($errors)
                ->flatten()
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->values();

            if ($flattened->isNotEmpty()) {
                return $flattened->implode(' ');
            }
        }

        $body = trim($response->body());

        return $body !== '' ? $body : 'Platform service request failed.';
    }
}
