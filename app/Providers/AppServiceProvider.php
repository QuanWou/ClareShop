<?php

namespace App\Providers;

use App\Modules\Catalog\Actions\ListVisibleCategoryTreeAction;
use App\Modules\Content\Support\SiteContentRegistry;
use App\Modules\Settings\Support\SiteSettingsRegistry;
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
        $this->app->scoped(ListVisibleCategoryTreeAction::class, fn (): ListVisibleCategoryTreeAction => new ListVisibleCategoryTreeAction);
        $this->app->scoped(SiteSettingsRegistry::class, fn (): SiteSettingsRegistry => new SiteSettingsRegistry);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('siteContent', app(SiteContentRegistry::class));
            $view->with('navigationCategories', app(ListVisibleCategoryTreeAction::class)->execute());
            $view->with('siteSettings', app(SiteSettingsRegistry::class));
        });
    }
}
