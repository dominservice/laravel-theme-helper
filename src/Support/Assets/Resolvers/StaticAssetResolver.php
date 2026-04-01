<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Assets\Resolvers;

use Dominservice\LaravelThemeHelper\Support\Themes\ThemeDefinition;

final class StaticAssetResolver implements AssetResolverInterface
{
    public function resolve(ThemeDefinition $theme): array
    {
        $links = [];
        foreach ($theme->cssEntries() as $entry) {
            $links[] = [
                'href' => $theme->assetPathToUrl($entry),
                'rel' => 'stylesheet',
            ];
        }

        $scripts = [];
        foreach ($theme->jsEntries() as $entry) {
            $scripts[] = [
                'href' => $theme->assetPathToUrl($entry),
                'defer' => true,
                'async' => false,
                'attrs' => [],
            ];
        }

        return ['links' => $links, 'scripts' => $scripts];
    }
}
