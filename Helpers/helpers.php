<?php
declare(strict_types=1);

use Dominservice\LaravelThemeHelper\Facades\Theme;
use Dominservice\LaravelThemeHelper\ThemeStructuredData;

if (!function_exists('theme_structured_data')) {
    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     */
    function theme_structured_data(array $data, array $options = []): string
    {
        return ThemeStructuredData::smartStructuredData($data, $options);
    }
}

if (!function_exists('theme_structured_graph')) {
    /**
     * @param array<int,array<string,mixed>> $schemas
     * @param array<string,mixed> $options
     */
    function theme_structured_graph(array $schemas, array $options = []): string
    {
        return Theme::structuredGraph($schemas, $options);
    }
}

if (!function_exists('theme_current_theme')) {
    function theme_current_theme(string $context = 'public'): mixed
    {
        return Theme::currentTheme($context);
    }
}

if (!function_exists('theme_current_layout')) {
    function theme_current_layout(string $context = 'public'): ?string
    {
        $theme = Theme::currentTheme($context);

        return method_exists($theme, 'layout') ? $theme->layout() : null;
    }
}

if (!function_exists('theme_sitemap')) {
    function theme_sitemap(): mixed
    {
        return Theme::sitemap();
    }
}

if (!function_exists('theme_robots')) {
    function theme_robots(): mixed
    {
        return Theme::robots();
    }
}

if (!function_exists('theme_schema_preset')) {
    function theme_schema_preset(string $preset, array $data = []): array
    {
        return Theme::schemaPreset($preset, $data);
    }
}

if (!function_exists('theme_picture')) {
    function theme_picture(array $sources, array $imgAttributes = [], array $pictureAttributes = []): string
    {
        return Theme::images()->picture($sources, $imgAttributes, $pictureAttributes);
    }
}

if (!function_exists('theme_use')) {
    function theme_use(string $themeKey, ?string $context = null): mixed
    {
        return Theme::useTheme($themeKey, $context);
    }
}
