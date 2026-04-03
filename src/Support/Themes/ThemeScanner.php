<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Themes;

final class ThemeScanner
{
    /** @param array<string,mixed> $config */
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    public function manifestFilename(): string
    {
        return (string) ($this->config['manifest_filename'] ?? 'theme.json');
    }

    /** @return array<int,string> */
    public function scanPaths(): array
    {
        return array_values(array_filter((array) ($this->config['scan_paths'] ?? []), 'is_string'));
    }

    public function registerInto(ThemeRegistry $registry): void
    {
        foreach ($this->discover() as $key => $themeConfig) {
            if (! $registry->has($key)) {
                $registry->register($key, $themeConfig);
            }
        }
    }

    /** @return array<string,array<string,mixed>> */
    public function discover(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $themes = [];

        foreach ($this->scanPaths() as $basePath) {
            if (! is_dir($basePath)) {
                continue;
            }

            $entries = scandir($basePath) ?: [];
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $themeDir = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;
                if (! is_dir($themeDir)) {
                    continue;
                }

                $manifestPath = $themeDir . DIRECTORY_SEPARATOR . $this->manifestFilename();
                if (! is_file($manifestPath)) {
                    continue;
                }

                $manifest = ThemeManifest::fromPath($manifestPath);
                $themes[$manifest->key()] = $manifest->toThemeConfig();
            }
        }

        return $themes;
    }
}
