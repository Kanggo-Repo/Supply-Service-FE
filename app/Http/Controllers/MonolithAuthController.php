<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\KeycloakOidcService;
use App\Services\Platform\PlatformServiceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class MonolithAuthController extends Controller
{
    public function __construct(
        private readonly KeycloakOidcService $keycloakOidcService,
        private readonly PlatformServiceClient $platformServiceClient,
    ) {}

    public function login(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->intended(route('materials.index'));
        }

        return view('auth.login');
    }

    public function redirectToMonolith(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(20));
        $codeVerifier = bin2hex(random_bytes(32));

        $request->session()->put('oidc_state', $state);
        $request->session()->put('oidc_code_verifier', $codeVerifier);

        return redirect()->away($this->keycloakOidcService->authorizationUrl(
            state: $state,
            codeVerifier: $codeVerifier,
            redirectUri: route('auth.consume'),
        ));
    }

    public function consume(Request $request): RedirectResponse
    {
        if ($request->string('state')->toString() !== $request->session()->pull('oidc_state')) {
            return redirect()->route('login');
        }

        $codeVerifier = $request->session()->pull('oidc_code_verifier');
        $code = $request->string('code')->toString();

        if (! is_string($codeVerifier) || $codeVerifier === '' || $code === '') {
            return redirect()->route('login');
        }

        $tokens = $this->keycloakOidcService->exchangeCode(
            code: $code,
            codeVerifier: $codeVerifier,
            redirectUri: route('auth.consume'),
        );

        $request->session()->put('platform_access_token', $tokens['access_token'] ?? null);
        $request->session()->put('platform_refresh_token', $tokens['refresh_token'] ?? null);
        $request->session()->put('platform_id_token', $tokens['id_token'] ?? null);
        $request->session()->put('platform_token_expires_at', now()->addSeconds((int) ($tokens['expires_in'] ?? 0))->timestamp);

        $accessToken = $tokens['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            return redirect()->route('login');
        }

        $me = $this->platformServiceClient->me($accessToken);
        $navigation = $this->platformServiceClient->navigation($accessToken);

        $user = $this->upsertUser($me);

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();
        $this->storeNavigationContext($request, $navigation);

        if (($navigation['pending_access'] ?? false) === true) {
            return redirect()->route('service.access.pending');
        }

        if (! in_array('supply', (array) ($navigation['allowed_services'] ?? []), true)) {
            $preferredUrl = $this->resolvePreferredServiceUrl((string) ($navigation['preferred_app'] ?? ''));

            if ($preferredUrl !== null) {
                return redirect()->away($preferredUrl);
            }

            return redirect()->route('service.access.pending');
        }

        return redirect()->intended(route('materials.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $idTokenHint = $request->session()->pull('platform_id_token');

        $request->session()->forget([
            'platform_access_token',
            'platform_refresh_token',
            'platform_token_expires_at',
            'platform_allowed_services',
            'platform_blocked_services',
            'platform_pending_services',
            'platform_pending_access',
            'platform_preferred_app',
            'oidc_state',
            'oidc_code_verifier',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::guard('web')->logout();

        return redirect()->away($this->keycloakOidcService->logoutUrl(
            postLogoutRedirectUri: route('login'),
            idTokenHint: is_string($idTokenHint) ? $idTokenHint : null,
        ));
    }

    private function upsertUser(array $payload): User
    {
        $identity = is_array($payload['identity'] ?? null) ? $payload['identity'] : [];
        $subject = trim((string) ($identity['subject'] ?? ''));
        $email = trim((string) ($identity['email'] ?? ''));
        $name = trim((string) ($identity['name'] ?? ''));

        if ($subject === '' || $email === '') {
            throw new RuntimeException('Platform identity payload is incomplete.');
        }

        $authSubject = 'keycloak:'.$subject;

        $user = User::query()
            ->where('auth_provider', 'keycloak')
            ->where('auth_subject', $authSubject)
            ->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = new User;
            $user->password = Str::random(64);
        }

        $roles = is_array($payload['roles'] ?? null) ? $payload['roles'] : [];
        $permissions = is_array($payload['permissions'] ?? null) ? $payload['permissions'] : [];

        $user->fill([
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'auth_provider' => 'keycloak',
            'auth_subject' => $authSubject,
            'role_snapshot' => $this->normalizeStringList($roles),
            'permission_snapshot' => $this->normalizeStringList($permissions),
            'last_login_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
        ]);
        $user->save();

        return $user->fresh();
    }

    private function storeNavigationContext(Request $request, array $navigation): void
    {
        $request->session()->put([
            'platform_allowed_services' => $this->normalizeStringList($navigation['allowed_services'] ?? []),
            'platform_blocked_services' => $this->normalizeStringList($navigation['blocked_services'] ?? []),
            'platform_pending_services' => $this->normalizeStringList($navigation['pending_services'] ?? []),
            'platform_pending_access' => (bool) ($navigation['pending_access'] ?? false),
            'platform_preferred_app' => trim((string) ($navigation['preferred_app'] ?? '')),
        ]);
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

    private function normalizeStringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values,
        )));
    }
}
