<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Dominservice\LaravelThemeHelper\Support\Meta\MetaManager meta()
 * @method static \Dominservice\LaravelThemeHelper\Support\Assets\AssetManager assets()
 * @method static \Dominservice\LaravelThemeHelper\Support\Navigation\Breadcrumbs breadcrumbs()
 * @method static \Dominservice\LaravelThemeHelper\Support\Localization\Hreflang hreflang()
 * @method static string structured(array $data, array $options = [])
 * @method static array structuredErrors()
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
