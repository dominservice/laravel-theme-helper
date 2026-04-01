<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Assets\Resolvers;

use Dominservice\LaravelThemeHelper\Support\Themes\ThemeDefinition;
use RuntimeException;

final class MixAssetResolver implements AssetResolverInterface
{
    public function resolve(ThemeDefinition $theme): array
    {
        $manifestPath = $theme->manifestPath();
        if ($manifestPath === null || !is_file($manifestPath)) {
            throw new RuntimeException(sprintf('Mix manifest not found for theme [%s].', $theme->key()));
        }

        /** @var array<string,string> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $links = [];
        foreach ($theme->cssEntries() as $entry) {
            $resolved = $manifest[$this->normalizeEntry($entry)] ?? $entry;
            $links[] = [
                'href' => $theme->assetPathToUrl($resolved),
                'rel' => 'stylesheet',
            ];
        }

        $scripts = [];
        foreach ($theme->jsEntries() as $entry) {
            $resolved = $manifest[$this->normalizeEntry($entry)] ?? $entry;
            $scripts[] = [
                'href' => $theme->assetPathToUrl($resolved),
                'defer' => true,
                'async' => false,
                'attrs' => [],
            ];
        }

        return ['links' => $links, 'scripts' => $scripts];
    }

    private function normalizeEntry(string $entry): string
    {
        return '/' . ltrim($entry, '/');
    }
}
