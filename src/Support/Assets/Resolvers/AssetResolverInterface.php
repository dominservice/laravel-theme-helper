<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Assets\Resolvers;

use Dominservice\LaravelThemeHelper\Support\Themes\ThemeDefinition;

interface AssetResolverInterface
{
    /** @return array{links: array<int,array<string,mixed>>, scripts: array<int,array<string,mixed>>} */
    public function resolve(ThemeDefinition $theme): array;
}
