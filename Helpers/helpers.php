<?php
declare(strict_types=1);

use Dominservice\LaravelThemeHelper\Facades\Theme;
use Dominservice\LaravelThemeHelper\ThemeStructuredData;

if (!function_exists('theme_structured_data')) {
    /**
     * Szybki helper do generowania JSON-LD.
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     */
    function theme_structured_data(array $data, array $options = []): string
    {
        return ThemeStructuredData::smartStructuredData($data, $options);
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

if (!function_exists('theme_use')) {
    function theme_use(string $themeKey, ?string $context = null): mixed
    {
        return Theme::useTheme($themeKey, $context);
    }
}
