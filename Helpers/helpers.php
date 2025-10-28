<?php
declare(strict_types=1);

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
