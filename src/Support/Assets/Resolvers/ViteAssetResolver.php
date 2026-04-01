<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Assets\Resolvers;

use Dominservice\LaravelThemeHelper\Support\Themes\ThemeDefinition;
use RuntimeException;

final class ViteAssetResolver implements AssetResolverInterface
{
    public function resolve(ThemeDefinition $theme): array
    {
        $hotUrl = $this->resolveHotUrl($theme);
        if ($hotUrl !== null) {
            return $this->resolveHot($theme, $hotUrl);
        }

        return $this->resolveManifest($theme);
    }

    private function resolveHot(ThemeDefinition $theme, string $hotUrl): array
    {
        $links = [];
        $scripts = [[
            'href' => rtrim($hotUrl, '/') . '/@vite/client',
            'defer' => false,
            'async' => false,
            'attrs' => ['type' => 'module'],
        ]];

        foreach ($theme->cssEntries() as $entry) {
            $links[] = [
                'href' => rtrim($hotUrl, '/') . '/' . ltrim($entry, '/'),
                'rel' => 'stylesheet',
            ];
        }

        foreach ($theme->jsEntries() as $entry) {
            $scripts[] = [
                'href' => rtrim($hotUrl, '/') . '/' . ltrim($entry, '/'),
                'defer' => false,
                'async' => false,
                'attrs' => ['type' => 'module'],
            ];
        }

        return ['links' => $links, 'scripts' => $scripts];
    }

    private function resolveManifest(ThemeDefinition $theme): array
    {
        $manifestPath = $theme->manifestPath();
        if ($manifestPath === null || !is_file($manifestPath)) {
            throw new RuntimeException(sprintf('Vite manifest not found for theme [%s].', $theme->key()));
        }

        /** @var array<string,array<string,mixed>> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $links = [];
        $scripts = [];
        $seenCss = [];
        $seenJs = [];

        foreach ($theme->cssEntries() as $entry) {
            $cssPath = $this->resolveManifestAssetPath($manifest, $entry);
            if ($cssPath !== null && !isset($seenCss[$cssPath])) {
                $seenCss[$cssPath] = true;
                $links[] = [
                    'href' => $theme->assetPathToUrl($cssPath),
                    'rel' => 'stylesheet',
                ];
            }
        }

        foreach ($theme->jsEntries() as $entry) {
            $assets = $this->collectManifestAssets($manifest, $entry);
            foreach ($assets['css'] as $cssPath) {
                if (!isset($seenCss[$cssPath])) {
                    $seenCss[$cssPath] = true;
                    $links[] = [
                        'href' => $theme->assetPathToUrl($cssPath),
                        'rel' => 'stylesheet',
                    ];
                }
            }

            foreach ($assets['js'] as $jsPath) {
                if (!isset($seenJs[$jsPath])) {
                    $seenJs[$jsPath] = true;
                    $scripts[] = [
                        'href' => $theme->assetPathToUrl($jsPath),
                        'defer' => false,
                        'async' => false,
                        'attrs' => ['type' => 'module'],
                    ];
                }
            }
        }

        return ['links' => $links, 'scripts' => $scripts];
    }

    private function resolveHotUrl(ThemeDefinition $theme): ?string
    {
        $hotFile = $theme->hotFilePath();
        if ($hotFile !== null && is_file($hotFile)) {
            $contents = trim((string) file_get_contents($hotFile));
            if ($contents !== '') {
                return rtrim($contents, '/');
            }
        }

        return $theme->devServerUrl();
    }

    /** @param array<string,array<string,mixed>> $manifest */
    private function resolveManifestAssetPath(array $manifest, string $entry): ?string
    {
        $item = $manifest[$entry] ?? null;
        if (!is_array($item)) {
            return null;
        }

        $file = $item['file'] ?? null;
        return is_string($file) ? $file : null;
    }

    /** @param array<string,array<string,mixed>> $manifest
     *  @return array{css: array<int,string>, js: array<int,string>}
     */
    private function collectManifestAssets(array $manifest, string $entry): array
    {
        $css = [];
        $js = [];
        $visited = [];
        $stack = [$entry];

        while ($stack !== []) {
            $current = array_pop($stack);
            if (!is_string($current) || isset($visited[$current]) || !isset($manifest[$current])) {
                continue;
            }

            $visited[$current] = true;
            $item = $manifest[$current];

            if (!empty($item['file']) && is_string($item['file'])) {
                $js[] = $item['file'];
            }

            foreach ((array) ($item['css'] ?? []) as $cssFile) {
                if (is_string($cssFile)) {
                    $css[] = $cssFile;
                }
            }

            foreach ((array) ($item['imports'] ?? []) as $import) {
                if (is_string($import)) {
                    $stack[] = $import;
                }
            }
        }

        return [
            'css' => array_values(array_unique($css)),
            'js' => array_values(array_unique($js)),
        ];
    }
}
