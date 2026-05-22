<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\MonolithAuthBridgeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class MonolithAuthController extends Controller
{
    public function __construct(
        private readonly MonolithAuthBridgeClient $bridgeClient,
    ) {}

    public function login(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->intended(route('materials.index'));
        }

        if (! $this->bridgeClient->isEnabled()) {
            return redirect()
                ->route('materials.index')
                ->with('error', 'Monolith auth bridge belum diaktifkan pada Supply FE.');
        }

        return view('auth.login');
    }

    public function redirectToMonolith(): RedirectResponse
    {
        if (! $this->bridgeClient->isEnabled()) {
            return redirect()
                ->route('materials.index')
                ->with('error', 'Monolith auth bridge belum diaktifkan pada Supply FE.');
        }

        return redirect()->away(
            $this->bridgeClient->handoffStartUrl(route('auth.consume')),
        );
    }

    public function consume(Request $request): RedirectResponse
    {
        if (! $this->bridgeClient->isEnabled()) {
            return redirect()
                ->route('materials.index')
                ->with('error', 'Monolith auth bridge belum diaktifkan pada Supply FE.');
        }

        $token = trim((string) $request->query('handoff_token', ''));
        if ($token === '') {
            return redirect()
                ->route('login')
                ->with('error', 'Token handoff login tidak ditemukan.');
        }

        try {
            $payload = $this->bridgeClient->redeem($token, route('auth.consume'));
            $userData = is_array($payload['data']['user'] ?? null) ? $payload['data']['user'] : [];
            $user = $this->upsertUser($userData);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->with('error', 'Login FE melalui monolith gagal: '.$exception->getMessage());
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('materials.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $logoutUrl = null;
        if ($this->bridgeClient->isEnabled()) {
            $logoutUrl = $this->bridgeClient->handoffLogoutUrl(route('login'));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (is_string($logoutUrl) && trim($logoutUrl) !== '') {
            return redirect()->away($logoutUrl);
        }

        return redirect()->route('login');
    }

    /**
     * @param  array<string, mixed>  $userData
     */
    private function upsertUser(array $userData): User
    {
        $monolithUserId = trim((string) ($userData['id'] ?? ''));
        $email = trim((string) ($userData['email'] ?? ''));
        $name = trim((string) ($userData['name'] ?? ''));

        if ($monolithUserId === '' || $email === '') {
            throw new RuntimeException('Monolith auth payload is incomplete.');
        }

        $subject = 'monolith:'.$monolithUserId;

        $user = User::query()
            ->where('auth_provider', 'monolith')
            ->where('auth_subject', $subject)
            ->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = new User();
            $user->password = Str::random(64);
        }

        $user->fill([
            'name' => $name !== '' ? $name : $email,
            'email' => $email,
            'auth_provider' => 'monolith',
            'auth_subject' => $subject,
            'role_snapshot' => $this->normalizeStringList($userData['roles'] ?? []),
            'permission_snapshot' => $this->normalizeStringList($userData['permissions'] ?? []),
            'last_login_at' => Carbon::now(),
            'email_verified_at' => Carbon::now(),
        ]);
        $user->save();

        return $user->fresh();
    }

    /**
     * @return list<string>
     */
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
