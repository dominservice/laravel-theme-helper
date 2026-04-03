<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Themes;

use InvalidArgumentException;

final class ThemeManifest
{
    /** @param array<string,mixed> $data */
    private function __construct(
        private readonly string $path,
        private readonly array $data,
    ) {}

    public static function fromPath(string $path): self
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Theme manifest [%s] was not found.', $path));
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Theme manifest [%s] is not valid JSON.', $path));
        }

        return new self($path, $decoded);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function directory(): string
    {
        return dirname($this->path);
    }

    public function key(): string
    {
        $key = $this->data['key'] ?? $this->data['slug'] ?? basename($this->directory());

        return trim((string) $key);
    }

    /** @return array<string,mixed> */
    public function toThemeConfig(): array
    {
        $themePath = $this->directory();
        $data = $this->data;
        $entries = (array) ($data['entries'] ?? []);

        return [
            'label' => $data['label'] ?? $data['name'] ?? $this->key(),
            'context' => $data['context'] ?? 'public',
            'driver' => $data['driver'] ?? 'static',
            'layout' => $data['layout'] ?? null,
            'view_path' => $data['view_path'] ?? null,
            'build_config_path' => $this->normalizePath($data['build_config_path'] ?? null, $themePath),
            'manifest_path' => $this->normalizePath($data['manifest_path'] ?? null, $themePath),
            'hot_file_path' => $this->normalizePath($data['hot_file_path'] ?? null, $themePath),
            'dev_server_url' => $data['dev_server_url'] ?? null,
            'public_path' => $data['public_path'] ?? null,
            'asset_url' => $data['asset_url'] ?? null,
            'deploy_target' => $this->normalizePath($data['deploy_target'] ?? null, $themePath),
            'entries' => [
                'css' => array_values(array_filter((array) ($entries['css'] ?? []), 'is_string')),
                'js' => array_values(array_filter((array) ($entries['js'] ?? []), 'is_string')),
            ],
            'meta' => (array) ($data['meta'] ?? []),
            'description' => $data['description'] ?? null,
            'version' => $data['version'] ?? null,
            'author' => $data['author'] ?? null,
            'preview_image' => $this->normalizePath($data['preview_image'] ?? null, $themePath),
            'settings' => (array) ($data['settings'] ?? []),
            'manifest_path_source' => $this->path,
            'source_path' => $themePath,
        ];
    }

    private function normalizePath(mixed $value, string $themePath): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        if (preg_match('#^(?:[A-Za-z]:\\|/)#', $value) === 1) {
            return $value;
        }

        return rtrim($themePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($value, DIRECTORY_SEPARATOR);
    }
}
