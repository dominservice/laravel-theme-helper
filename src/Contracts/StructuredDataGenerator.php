<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Contracts;

interface StructuredDataGenerator
{
    /**
     * Generuje <script type="application/ld+json">…</script> na podstawie danych.
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $options
     */
    public static function smartStructuredData(array $data, array $options = []): string;

    /**
     * Zwraca i czyści bufor błędów walidacji (gdy on_invalid = 'skip').
     *
     * @return array<int,string>
     */
    public static function pullSchemaErrors(): array;
}
