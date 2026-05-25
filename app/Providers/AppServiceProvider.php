<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
            $view->with([
                'sidebarStoresMissingMapCount' => 0,
                'sidebarProjectDraftCount' => 0,
            ]);
        });
    }
}
