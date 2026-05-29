<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Supply\SupplyServiceClient;
use App\Support\Auth\SupplyPermissionGate;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->isSuperAdmin()) {
                return true;
            }

            if ($user instanceof User && in_array('platform_operator', $user->role_snapshot ?? [], true)) {
                return true;
            }

            if ($user instanceof User && $user->hasPermission($ability)) {
                return true;
            }

            return null;
        });

        Blade::directive('format', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::format($expression); ?>";
        });

        Blade::directive('number', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::format($expression); ?>";
        });

        Blade::directive('formatResult', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::formatResult($expression); ?>";
        });

        Blade::directive('numberResult', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::formatResult($expression); ?>";
        });

        Blade::directive('currency', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::currency($expression); ?>";
        });

        Blade::directive('price', function ($expression) {
            return "<?php echo \\App\\Helpers\\NumberHelper::formatFixed($expression, 0); ?>";
        });

        View::composer('layouts.app', function ($view): void {
            $user = request()->user();
            $sidebarStoresMissingMapCount = 0;
            $sidebarProjectDraftCount = 0;

            if ($user instanceof User) {
                /** @var SupplyPermissionGate $permissionGate */
                $permissionGate = app(SupplyPermissionGate::class);

                if ($permissionGate->allowsAny($user, [
                    'stores.view',
                    'stores.create',
                    'stores.update',
                    'stores.delete',
                    'stores.manage',
                ])) {
                    try {
                        /** @var SupplyServiceClient $supplyServiceClient */
                        $supplyServiceClient = app(SupplyServiceClient::class);
                        $payload = $supplyServiceClient->storeSidebarSummary($user);
                        $sidebarStoresMissingMapCount = max(0, (int) data_get($payload, 'data.stores_missing_map_count', 0));
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                }

                if (trim((string) config('services.calculation_service.base_url', '')) !== ''
                    && $permissionGate->allowsAny($user, [
                        'calculations.view',
                        'calculations.create',
                        'calculations.update',
                        'calculations.delete',
                        'calculations.export',
                        'calculations.manage',
                        'projects.view',
                        'projects.create',
                        'projects.update',
                        'projects.delete',
                        'projects.manage',
                    ])) {
                    $sidebarProjectDraftCount = $this->resolveSidebarProjectDraftCount();
                }
            }

            $view->with([
                'sidebarStoresMissingMapCount' => $sidebarStoresMissingMapCount,
                'sidebarProjectDraftCount' => $sidebarProjectDraftCount,
            ]);
        });
    }

    private function resolveSidebarProjectDraftCount(): int
    {
        $baseUrl = rtrim((string) config('services.calculation_service.base_url'), '/');
        if ($baseUrl === '') {
            return 0;
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->timeout(4)
                ->get('/api/v1/calculation-drafts', [
                    'status' => 'draft',
                ]);

            if (! $response->successful()) {
                return 0;
            }

            $data = $response->json('data', []);

            return is_array($data) ? count($data) : 0;
        } catch (Throwable) {
            return 0;
        }
    }
}
