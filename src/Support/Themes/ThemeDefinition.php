<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Themes;

final class ThemeDefinition
{
    /** @param array<string,mixed> $config */
    public function __construct(
        private readonly string $key,
        private readonly array $config,
    ) {}

    public static function fromArray(string $key, array $config): self
    {
        $config['key'] = $key;
        $config['entries'] = array_replace(['css' => [], 'js' => []], (array) ($config['entries'] ?? []));

        return new self($key, $config);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return (string) ($this->config['label'] ?? $this->key);
    }

    public function context(): string
    {
        return (string) ($this->config['context'] ?? 'public');
    }

    public function driver(): string
    {
        return (string) ($this->config['driver'] ?? 'static');
    }

    public function layout(): ?string
    {
        $value = $this->config['layout'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function viewPath(): ?string
    {
        $value = $this->config['view_path'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function buildConfigPath(): ?string
    {
        $value = $this->config['build_config_path'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function manifestPath(): ?string
    {
        $value = $this->config['manifest_path'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function hotFilePath(): ?string
    {
        $value = $this->config['hot_file_path'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public function devServerUrl(): ?string
    {
        $value = $this->config['dev_server_url'] ?? null;
        return is_string($value) && $value !== '' ? rtrim($value, '/') : null;
    }

    public function publicPath(): ?string
    {
        $value = $this->config['public_path'] ?? null;
        return is_string($value) && $value !== '' ? trim($value, '/') : null;
    }

    public function assetUrl(): ?string
    {
        $value = $this->config['asset_url'] ?? null;
        return is_string($value) && $value !== '' ? rtrim($value, '/') : null;
    }

    public function deployTarget(): ?string
    {
        $value = $this->config['deploy_target'] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array<int,string> */
    public function cssEntries(): array
    {
        return array_values(array_filter((array) ($this->config['entries']['css'] ?? []), 'is_string'));
    }

    /** @return array<int,string> */
    public function jsEntries(): array
    {
        return array_values(array_filter((array) ($this->config['entries']['js'] ?? []), 'is_string'));
    }

    /** @return array<string,mixed> */
    public function meta(): array
    {
        return (array) ($this->config['meta'] ?? []);
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return $this->config;
    }

    public function assetPathToUrl(string $path): string
    {
        if (preg_match('#^(https?:)?//#', $path) === 1) {
            return $path;
        }

        $normalized = '/' . ltrim($path, '/');
        if ($this->assetUrl() !== null) {
            return $this->assetUrl() . $normalized;
        }

        if (function_exists('asset')) {
            return asset(ltrim($normalized, '/'));
        }

        return $normalized;
    }
}
