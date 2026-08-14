<?php

namespace App\Providers;

use App\Modules\Content\Support\SiteContentRegistry;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(SiteContentRegistry::class, fn (): SiteContentRegistry => new SiteContentRegistry);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('siteContent', app(SiteContentRegistry::class));
        });
    }
}
