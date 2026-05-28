<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceAccess
{
    public function handle(Request $request, Closure $next, string $serviceCode): Response
    {
        if (! $request->session()->has('platform_allowed_services') && ! $request->session()->has('platform_pending_access')) {
            return $next($request);
        }

        $pendingAccess = (bool) $request->session()->get('platform_pending_access', false);
        $allowedServices = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            (array) $request->session()->get('platform_allowed_services', []),
        )));

        if ($pendingAccess) {
            return redirect()->route('service.access.pending');
        }

        if (in_array($serviceCode, $allowedServices, true)) {
            return $next($request);
        }

        $preferredUrl = $this->resolvePreferredServiceUrl((string) $request->session()->get('platform_preferred_app', ''));

        if ($preferredUrl !== null) {
            return redirect()->away($preferredUrl);
        }

        abort(403, 'Akun Anda belum memiliki akses ke service ini.');
    }

    private function resolvePreferredServiceUrl(string $preferredApp): ?string
    {
        $baseUrl = match ($preferredApp) {
            'platform' => (string) config('services.platform_fe.base_url'),
            'calculation' => (string) config('services.calculation_fe.base_url'),
            default => '',
        };

        $normalized = rtrim(trim($baseUrl), '/');

        return $normalized !== '' ? $normalized : null;
    }
}
