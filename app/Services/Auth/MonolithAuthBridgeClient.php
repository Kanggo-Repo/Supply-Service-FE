<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MonolithAuthBridgeClient
{
    public function isEnabled(): bool
    {
        return (bool) config('services.monolith_auth.enabled', false);
    }

    public function handoffStartUrl(string $returnTo): string
    {
        return $this->joinBaseAndPath($this->handoffStartPath())
            .'?'.http_build_query(['return_to' => $returnTo], '', '&', PHP_QUERY_RFC3986);
    }

    public function handoffLogoutUrl(string $returnTo): string
    {
        return $this->joinBaseAndPath($this->handoffLogoutPath())
            .'?'.http_build_query(['return_to' => $returnTo], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array<string, mixed>
     */
    public function redeem(string $token, string $consumer): array
    {
        $response = Http::timeout(15)
            ->connectTimeout(10)
            ->acceptJson()
            ->withOptions(['verify' => (bool) config('services.monolith_auth.verify_ssl', true)])
            ->post($this->joinBaseAndPath($this->handoffRedeemPath()), [
                'token' => $token,
                'consumer' => $consumer,
            ]);

        $payload = $response->json();
        if (! $response->successful()) {
            $message = is_array($payload)
                ? (string) ($payload['message'] ?? 'Monolith auth handoff redeem failed.')
                : 'Monolith auth handoff redeem failed.';

            throw new RuntimeException($message);
        }

        return is_array($payload) ? $payload : [];
    }

    private function joinBaseAndPath(string $path): string
    {
        $baseUrl = rtrim((string) config('services.monolith_auth.base_url', ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Monolith auth base URL is not configured.');
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    private function handoffStartPath(): string
    {
        return trim((string) config('services.monolith_auth.handoff_start_path', '/auth/handoff/start'));
    }

    private function handoffRedeemPath(): string
    {
        return trim((string) config('services.monolith_auth.handoff_redeem_path', '/api/internal/auth/handoffs/redeem'));
    }

    private function handoffLogoutPath(): string
    {
        return trim((string) config('services.monolith_auth.handoff_logout_path', '/auth/handoff/logout'));
    }
}
