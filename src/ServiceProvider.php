<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper;

use Dominservice\LaravelThemeHelper\Support\Assets\AssetManager;
use Dominservice\LaravelThemeHelper\Support\Assets\Resolvers\MixAssetResolver;
use Dominservice\LaravelThemeHelper\Support\Assets\Resolvers\StaticAssetResolver;
use Dominservice\LaravelThemeHelper\Support\Assets\Resolvers\ViteAssetResolver;
use Dominservice\LaravelThemeHelper\Support\Localization\Hreflang;
use Dominservice\LaravelThemeHelper\Support\Meta\MetaManager;
use Dominservice\LaravelThemeHelper\Support\Navigation\Breadcrumbs;
use Dominservice\LaravelThemeHelper\Support\Seo\Images\ResponsiveImageRenderer;
use Dominservice\LaravelThemeHelper\Support\Seo\Robots\RobotsTxtBuilder;
use Dominservice\LaravelThemeHelper\Support\Seo\Schema\SchemaPresetFactory;
use Dominservice\LaravelThemeHelper\Support\Seo\Sitemap\SitemapBuilder;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeContextManager;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeRegistry;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeScanner;
use Illuminate\Support\ServiceProvider as BaseProvider;

final class ServiceProvider extends BaseProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/theme-helper.php', 'theme-helper');

        $this->app->singleton('dominservice.theme', function () {
            $structured = new ThemeStructuredData();
            $meta = new MetaManager();
            $assets = new AssetManager();
            $breadcrumbs = new Breadcrumbs();
            $hreflang = new Hreflang();
            $sitemap = new SitemapBuilder((array) config('theme-helper.seo.sitemap', []));
            $robots = new RobotsTxtBuilder((array) config('theme-helper.seo.robots', []));
            $schemaPresets = new SchemaPresetFactory((array) config('theme-helper.seo.schema_presets', []));
            $images = new ResponsiveImageRenderer((array) config('theme-helper.seo.images', []));
            $registry = new ThemeRegistry((array) config('theme-helper.themes', []));

            (new ThemeScanner((array) config('theme-helper.discovery', [])))->registerInto($registry);

            $contexts = new ThemeContextManager($registry, (array) config('theme-helper.contexts', []));

            $assets->setThemeSupport($registry, $contexts, [
                'mix' => new MixAssetResolver(),
                'vite' => new ViteAssetResolver(),
                'static' => new StaticAssetResolver(),
            ]);

            return new ThemeManager(
                $structured,
                $meta,
                $assets,
                $breadcrumbs,
                $hreflang,
                $sitemap,
                $robots,
                $schemaPresets,
                $images,
                $registry,
                $contexts
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/theme-helper.php' => config_path('theme-helper.php'),
        ], 'config');
    }
}
