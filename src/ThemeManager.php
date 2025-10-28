<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper;

use Dominservice\LaravelThemeHelper\Contracts\StructuredDataGenerator;
use Dominservice\LaravelThemeHelper\Schema\Types;
use Dominservice\LaravelThemeHelper\Support\Assets\AssetManager;
use Dominservice\LaravelThemeHelper\Support\Meta\MetaManager;
use Dominservice\LaravelThemeHelper\Support\Navigation\Breadcrumbs;
use Dominservice\LaravelThemeHelper\Support\Localization\Hreflang;

final class ThemeManager
{
    public function __construct(
        private readonly StructuredDataGenerator $structured,
        private readonly MetaManager $meta,
        private readonly AssetManager $assets,
        private readonly Breadcrumbs $breadcrumbs,
        private readonly Hreflang $hreflang,
    ) {}

    /** ========== Structured Data ========== */

    public function structured(array $data, array $options = []): string
    {
        return $this->structured->smartStructuredData($data, $options);
    }

    public function structuredErrors(): array
    {
        return $this->structured::pullSchemaErrors();
    }

    public function breadcrumbJsonLd(array $items, array $options = []): string
    {
        return $this->structured([
            'type'  => Types::BREADCRUMB,
            'items' => $this->breadcrumbs->normalizeItems($items),
        ], $options);
    }

    /** ========== META / HEAD ========== */

    public function meta(): MetaManager
    {
        return $this->meta;
    }

    /** ========== ASSETS ========== */

    public function assets(): AssetManager
    {
        return $this->assets;
    }

    /** ========== BREADCRUMBS (HTML) ========== */

    public function breadcrumbs(): Breadcrumbs
    {
        return $this->breadcrumbs;
    }

    /** ========== HREFLANG ========== */

    public function hreflang(): Hreflang
    {
        return $this->hreflang;
    }

    /** ========== Renderery gotowych fragmentów HEAD/BODY ========== */

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
