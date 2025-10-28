<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper;

use Dominservice\LaravelThemeHelper\Support\Assets\AssetManager;
use Dominservice\LaravelThemeHelper\Support\Localization\Hreflang;
use Dominservice\LaravelThemeHelper\Support\Meta\MetaManager;
use Dominservice\LaravelThemeHelper\Support\Navigation\Breadcrumbs;
use Illuminate\Support\ServiceProvider as BaseProvider;

final class ServiceProvider extends BaseProvider
{
    public function register(): void
    {
        $this->app->singleton('dominservice.theme', function () {
            $structured  = new ThemeStructuredData();
            $meta        = new MetaManager();
            $assets      = new AssetManager();
            $breadcrumbs = new Breadcrumbs();
            $hreflang    = new Hreflang();

            return new ThemeManager($structured, $meta, $assets, $breadcrumbs, $hreflang);
        });
    }

    public function boot(): void
    {
        // miejsce na publish config/assets w przyszłości
    }
}
