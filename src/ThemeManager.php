<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper;

use Dominservice\LaravelThemeHelper\Contracts\StructuredDataGenerator;
use Dominservice\LaravelThemeHelper\Schema\SchemaCollection;
use Dominservice\LaravelThemeHelper\Schema\Types;
use Dominservice\LaravelThemeHelper\Support\Assets\AssetManager;
use Dominservice\LaravelThemeHelper\Support\Localization\Hreflang;
use Dominservice\LaravelThemeHelper\Support\Meta\MetaManager;
use Dominservice\LaravelThemeHelper\Support\Navigation\Breadcrumbs;
use Dominservice\LaravelThemeHelper\Support\Seo\Images\ResponsiveImageRenderer;
use Dominservice\LaravelThemeHelper\Support\Seo\Robots\RobotsTxtBuilder;
use Dominservice\LaravelThemeHelper\Support\Seo\Schema\SchemaPresetFactory;
use Dominservice\LaravelThemeHelper\Support\Seo\Sitemap\SitemapBuilder;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeContextManager;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeDefinition;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeRegistry;

final class ThemeManager
{
    public function __construct(
        private readonly StructuredDataGenerator $structured,
        private readonly MetaManager $meta,
        private readonly AssetManager $assets,
        private readonly Breadcrumbs $breadcrumbs,
        private readonly Hreflang $hreflang,
        private readonly SitemapBuilder $sitemap,
        private readonly RobotsTxtBuilder $robots,
        private readonly SchemaPresetFactory $schemaPresets,
        private readonly ResponsiveImageRenderer $images,
        private readonly ThemeRegistry $registry,
        private readonly ThemeContextManager $contexts,
    ) {}

    public function structured(array $data, array $options = []): string
    {
        return $this->structured->smartStructuredData($data, $options);
    }

    public function structuredErrors(): array
    {
        return $this->structured::pullSchemaErrors();
    }

    public function schemaCollection(): SchemaCollection
    {
        return new SchemaCollection();
    }

    public function structuredGraph(array|SchemaCollection $schemas, array $options = []): string
    {
        if ($schemas instanceof SchemaCollection) {
            $schemas = $schemas->toArray();
        }

        return $this->structured([
            'schemas' => $schemas,
        ], $options);
    }

    public function breadcrumbJsonLd(array $items, array $options = []): string
    {
        return $this->structured([
            'type' => Types::BREADCRUMB,
            'items' => $this->breadcrumbs->normalizeItems($items),
        ], $options);
    }

    public function meta(): MetaManager
    {
        return $this->meta;
    }

    public function assets(): AssetManager
    {
        return $this->assets;
    }

    public function breadcrumbs(): Breadcrumbs
    {
        return $this->breadcrumbs;
    }

    public function hreflang(): Hreflang
    {
        return $this->hreflang;
    }

    public function sitemap(): SitemapBuilder
    {
        return clone $this->sitemap;
    }

    public function robots(): RobotsTxtBuilder
    {
        return clone $this->robots;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function schemaPreset(string $preset, array $data = []): array
    {
        return $this->schemaPresets->make($preset, $data);
    }

    public function images(): ResponsiveImageRenderer
    {
        return $this->images;
    }

    public function registry(): ThemeRegistry
    {
        return $this->registry;
    }

    public function contexts(): ThemeContextManager
    {
        return $this->contexts;
    }

    public function currentTheme(string $context = 'public'): ThemeDefinition
    {
        return $this->contexts->current($context);
    }

    public function useTheme(string $themeKey, ?string $context = null): self
    {
        $this->contexts->use($themeKey, $context);

        return $this;
    }

    public function renderHead(): string
    {
        $parts = [];
        $parts[] = $this->meta->renderStandard();
        $parts[] = $this->assets->renderHeadLinks();
        $parts[] = $this->hreflang->renderAlternates();

        return implode("\n", array_filter($parts));
    }

    public function renderBodyEnd(): string
    {
        return $this->assets->renderBodyScripts();
    }
}
