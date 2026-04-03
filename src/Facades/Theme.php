<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Dominservice\LaravelThemeHelper\Support\Meta\MetaManager meta()
 * @method static \Dominservice\LaravelThemeHelper\Support\Assets\AssetManager assets()
 * @method static \Dominservice\LaravelThemeHelper\Support\Navigation\Breadcrumbs breadcrumbs()
 * @method static \Dominservice\LaravelThemeHelper\Support\Localization\Hreflang hreflang()
 * @method static \Dominservice\LaravelThemeHelper\Support\Seo\Sitemap\SitemapBuilder sitemap()
 * @method static \Dominservice\LaravelThemeHelper\Support\Seo\Robots\RobotsTxtBuilder robots()
 * @method static array schemaPreset(string $preset, array $data = [])
 * @method static \Dominservice\LaravelThemeHelper\Support\Seo\Images\ResponsiveImageRenderer images()
 * @method static \Dominservice\LaravelThemeHelper\Support\Themes\ThemeRegistry registry()
 * @method static \Dominservice\LaravelThemeHelper\Support\Themes\ThemeContextManager contexts()
 * @method static \Dominservice\LaravelThemeHelper\Support\Themes\ThemeDefinition currentTheme(string $context = 'public')
 * @method static \Dominservice\LaravelThemeHelper\ThemeManager useTheme(string $themeKey, ?string $context = null)
 * @method static string structured(array $data, array $options = [])
 * @method static array structuredErrors()
 * @method static \Dominservice\LaravelThemeHelper\Schema\SchemaCollection schemaCollection()
 * @method static string structuredGraph(array|\Dominservice\LaravelThemeHelper\Schema\SchemaCollection $schemas, array $options = [])
 * @method static string breadcrumbJsonLd(array $items, array $options = [])
 * @method static string renderHead()
 * @method static string renderBodyEnd()
 */
final class Theme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dominservice.theme';
    }
}
