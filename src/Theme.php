<?php
/**
 * Class ThemeHelper
 *
 * Uniwersalny pomocnik do generowania:
 * - tagów meta SEO i społecznościowych (Open Graph, Twitter),
 * - linku kanonicznego,
 * - tagów `<link rel="alternate" hreflang="...">`,
 * - danych strukturalnych JSON-LD (Schema.org) z użyciem pakietu spatie/schema-org,
 * - zoptymalizowanego ładowania stylów CSS (preload, lazy-load),
 * - responsywnych tagów `<img>` i `<video>` (dostępne przez metody pomocnicze).
 *
 * 📦 Wymaga: composer require spatie/schema-org
 *
 * ## Przykładowe użycie:
 *
 * echo ThemeHelper::renderMetaTags(
 *     pageType: 'article',
 *     title: 'Jak zoptymalizować Laravel SEO',
 *     description: 'Kompletny przewodnik dla deweloperów Laravel.',
 *     keywords: 'laravel, seo, meta tags, json-ld',
 *     themeColor: '#d85a2d',
 *     logo: 'images/logo.png',
 *     styles: [
 *         'css/app.css' => false, // krytyczne – ładowane natychmiast
 *         'css/animations.css' => true // lazy-load
 *     ]
 * );
 *
 * echo ThemeHelper::smartStructuredData([
 *     'type' => ThemeHelper::SCHEMA_PRODUCT,
 *     'name' => 'Dom strawbale 70m²',
 *     'image' => ['images/dom.jpg'],
 *     'price' => 199000,
 *     'currency' => 'PLN',
 *     'availability' => true,
 *     'brand' => 'GreenHaus'
 * ]);
 *
 * ## Dostępne typy danych Schema.org (używane w smartStructuredData):
 * - SCHEMA_ORGANIZATION
 * - SCHEMA_LOCALBUSINESS
 * - SCHEMA_PERSON
 * - SCHEMA_PRODUCT
 * - SCHEMA_ARTICLE
 * - SCHEMA_NEWSARTICLE
 * - SCHEMA_BLOGPOSTING
 * - SCHEMA_BREADCRUMB
 * - SCHEMA_EVENT
 * - SCHEMA_RECIPE
 * - SCHEMA_VIDEOOBJECT
 * - SCHEMA_FAQPAGE
 * - SCHEMA_HOWTO
 *
 * ## Uwagi:
 * - Metoda renderMetaTags automatycznie wygeneruje <link rel="canonical">, <meta name="description"> itd.
 * - Parametr $styles może być:
 *     ['ścieżka.css' => true]  // lazy
 *     ['ścieżka.css' => false] // klasycznie
 * - W smartStructuredData można przekazać uproszczoną tablicę danych – helper automatycznie dopasuje strukturę JSON-LD.
 *
 * '{!! \App\Helpers\HTMLHelper::metaTagsRender(
 *      pageType: 'website',
 *      logo: '/assets/media/app/favicon-32x32.png',
 *      title: $titleApp ?? null,
 *      description: $descriptionApp ?? null,
 *      keywords: $keywordsApp ?? null,
 *      themeColor: 'rgb(216, 90, 45)',
 *      favicon: url('assets/media/app/favicon.ico'),
 *      manifest: public_path('build/manifest.json'),
 *      tileColor: '#ffffff',
 *      styles: ['resources/css/app.css' => false]
 * )  !!}
 *
 */


namespace App\Helpers;

use Spatie\SchemaOrg\Schema;

class Theme
{
    public const SCHEMA_ORGANIZATION   = 'Organization';
    public const SCHEMA_LOCALBUSINESS  = 'LocalBusiness';
    public const SCHEMA_PERSON         = 'Person';
    public const SCHEMA_PRODUCT        = 'Product';
    public const SCHEMA_ARTICLE        = 'Article';
    public const SCHEMA_NEWSARTICLE    = 'NewsArticle';
    public const SCHEMA_BLOGPOSTING    = 'BlogPosting';
    public const SCHEMA_BREADCRUMB     = 'BreadcrumbList';
    public const SCHEMA_EVENT          = 'Event';
    public const SCHEMA_RECIPE         = 'Recipe';
    public const SCHEMA_VIDEOOBJECT    = 'VideoObject';
    public const SCHEMA_FAQPAGE        = 'FAQPage';
    public const SCHEMA_HOWTO          = 'HowTo';

    public static function canonicalUrl($current = null, array $preserveQueryParams = [])
    {
        $current = $current ?? url()->full();

        // Usuń fragment (#) i przygotuj dane
        $parsedUrl = parse_url($current);
        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = str_replace('www.', '', $parsedUrl['host'] ?? '');
        $path = $parsedUrl['path'] ?? '';

        parse_str($parsedUrl['query'] ?? '', $query);
        $filteredQuery = array_intersect_key($query, array_flip($preserveQueryParams));
        $queryString = http_build_query($filteredQuery);

        $url = "{$scheme}://{$host}{$path}";
        return $queryString ? "{$url}?{$queryString}" : $url;
    }

    public static function renderMetaTags(
        ?string $pageType = 'website',
        ?string $logo = null,
        ?string $title = null,
        ?string $description = null,
        ?string $keywords = null,
        ?string $themeColor = null,
        ?string $favicon = null,
        ?string $manifest = null,
        ?string $tileColor = '#ffffff',
        ?array $styles = null
    ): string {
        $siteName = config('app.name');
        $fullTitle = ($title ? e($title).' - ' : '') . e($siteName);
        $canonical = self::canonicalUrl();
        $output = [];

        $output[] = '<base href="'.url('/').'"/>';
        $output[] = '<meta name="csrf-token" content="'.csrf_token().'">';
        $output[] = '<meta name="author" content="Mateusz Domin" />';
        $output[] = '<meta name="publisher" content="DSO-IT Mateusz Domin" />';
        $output[] = "<title>{$fullTitle}</title>";
        $output[] = '<meta charset="utf-8"/>';
        $output[] = '<meta name="robots" content="follow, index" />';
        $output[] = '<link rel="canonical" href="'.$canonical.'"/>';
        $output[] = '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>';

        if ($description) {
            $output[] = '<meta name="description" content="'.e($description).'" />';
        }
        if ($keywords) {
            $output[] = '<meta name="keywords" content="'.e($keywords).'" />';
        }

        // Twitter
        $output[] = '<meta name="twitter:site" content="'.$siteName.'"/>';
        $output[] = '<meta name="twitter:creator" content="DSO-IT Mateusz Domin"/>';
        $output[] = '<meta name="twitter:card" content="summary_large_image"/>';
        $output[] = '<meta name="twitter:title" content="'.$fullTitle.'"/>';
        if ($description) {
            $output[] = '<meta name="twitter:description" content="'.e($description).'" />';
        }

        // Open Graph
        $output[] = '<meta property="og:url" content="'.url('/').'"/>';
        $output[] = '<meta property="og:locale" content="'.str_replace('_', '-', app()->getLocale()).'"/>';
        $output[] = '<meta property="og:type" content="'.$pageType.'"/>';
        $output[] = '<meta property="og:site_name" content="'.$siteName.'"/>';
        $output[] = '<meta property="og:title" content="'.$fullTitle.'"/>';

        if ($logo) {
            $publicLogo = public_path($logo);
            if (file_exists($publicLogo)) {
                [$width, $height] = getimagesize($publicLogo);
                $output[] = '<meta property="og:image" content="'.url($logo).'"/>';
                $output[] = '<meta property="og:image:width" content="'.$width.'" />';
                $output[] = '<meta property="og:image:height" content="'.$height.'" />';
                $output[] = '<meta name="twitter:image" content="'.url($logo).'"/>';
                $output[] = '<meta name="msapplication-TileImage" content="'.url($logo).'">';
            }
        }

        if ($themeColor) {
            $output[] = '<link rel="manifest" href="' . $manifest . '">';
        }
        if ($favicon) {
            $output[] = '<link rel="icon" href="' . $favicon . '">';
        }
        if ($tileColor) {
            $output[] = '<meta name="msapplication-TileColor" content="' . $tileColor . '">';
        }
        if ($themeColor) {
            $output[] = '<meta name="theme-color" content="' . ($themeColor) . '">';
        }

        if (class_exists(new \Dominservice\DataLocaleParser\DataParser)) {
            $parameters = request()->route()->parameters();
            $route = request()->route()->getName();

            foreach((new \Dominservice\DataLocaleParser\DataParser)->getLanguagesFullData(config('data_locale_parser.allowed_locales')) as $localeCode => $localeData) {
                $localizedUrl = route_locale($localeCode, $route, $parameters);
                $output[] = '<link rel="alternate" hreflang="'.e($localeCode).'" href="'.e($localizedUrl).'">';
            }

            // Dla robotów nieznających języka
            $output[] = '<link rel="alternate" hreflang="x-default" href="'.e(route_locale(config('app.fallback_locale'), $route, $parameters)).'">';
        } else if (class_exists(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::class)) {
            foreach (\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $localeCode => $localeData) {
                $localizedUrl = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($localeCode, null, [], true);
                $output[] = '<link rel="alternate" hreflang="'.e($localeCode).'" href="'.e($localizedUrl).'">';
            }

            // Dla robotów nieznających języka
            $output[] = '<link rel="alternate" hreflang="x-default" href="'.e(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL(config('app.fallback_locale'))).'">';
        }

        if ($styles) {
            foreach ($styles as $style => $isLazy) {
                $output[] = self::css($style, $isLazy);
            }
        }

        return implode("\n", $output);
    }

    /**
     * @param $style
     * @param mixed $isLazy
     * @return string
     */
    public static function css($style, mixed $isLazy = false)
    {
        if (is_int($style)) {
            $style = $isLazy;
            $isLazy = false;
        }

        $output = [];
        $href = asset($style);

        if ($isLazy) {
            $output[] = '<link rel="stylesheet" href="' . $href . '" type="text/css" media="none" onload="if(media!=\'all\')media=\'all\'">';
            $output[] = '<link rel="preload" href="' . $href . '" as="style">';
            $output[] = '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';
        } else {
            $output[] = '<link rel="preload" href="' . $href . '" as="style">';
            $output[] = '<link rel="stylesheet" href="' . $href . '">';
        }

        return implode("\n", $output);
    }

    public static function smartStructuredData(array $data): string
    {
        $type = $data['type'] ?? self::SCHEMA_ORGANIZATION;
        $schema = null;

        switch ($type) {
            case self::SCHEMA_PRODUCT:
                $schema = Schema::product()
                    ->name($data['name'] ?? config('app.name'))
                    ->image(array_map(fn($i)=>asset($i), (array)($data['image'] ?? [])))
                    ->description($data['description'] ?? '')
                    ->sku($data['sku'] ?? null)
                    ->brand(Schema::brand()->name($data['brand'] ?? config('app.name')))
                    ->offers(Schema::offer()
                        ->price($data['price'] ?? 0)
                        ->priceCurrency($data['currency'] ?? 'PLN')
                        ->availability($data['availability'] ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock')
                        ->url($data['url'] ?? url()->current()));
                break;

            case self::SCHEMA_ARTICLE:
            case self::SCHEMA_NEWSARTICLE:
            case self::SCHEMA_BLOGPOSTING:
                $schema = Schema::{$type}()
                    ->headline($data['title'] ?? config('app.name'))
                    ->datePublished($data['datePublished'] ?? now()->toIso8601String())
                    ->dateModified($data['dateModified'] ?? now()->toIso8601String())
                    ->author(Schema::person()->name($data['author'] ?? 'Autor'))
                    ->publisher(Schema::organization()
                        ->name(config('app.name'))
                        ->logo(Schema::imageObject()->url(asset('images/logo.png'))))
                    ->mainEntityOfPage(Schema::webPage()->id($data['url'] ?? url()->current()));
                break;

            case self::SCHEMA_BREADCRUMB:
                $schema = Schema::breadcrumbList();
                foreach (($data['items'] ?? []) as $i => $item) {
                    $schema->itemListElement(Schema::listItem()
                        ->position($i + 1)
                        ->name($item['name'])
                        ->item($item['url']));
                }
                break;

            case self::SCHEMA_EVENT:
                $schema = Schema::event()
                    ->name($data['name'])
                    ->startDate($data['startDate'])
                    ->location(Schema::place()->name($data['locationName'])->address($data['locationAddress']));
                break;

            // ... obsługa kolejnych typów ...

            case self::SCHEMA_ORGANIZATION:
            case self::SCHEMA_LOCALBUSINESS:
            case self::SCHEMA_PERSON:
            default:
                $schema = Schema::{$type}()
                    ->name($data['name'] ?? config('app.name'))
                    ->url($data['url'] ?? url()->current());
                if (!empty($data['logo'])) {
                    $schema->logo(asset($data['logo']));
                }
                if (!empty($data['sameAs'])) {
                    $schema->sameAs((array)$data['sameAs']);
                }
        }

        // Dodaj wspólne atrybuty
        if (!(isset($data['noContext']) ? $data['noContext'] : true)) {
            $schema->context('https://schema.org');
        }
        if (!empty(isset($data['inLanguage']) ? $data['inLanguage'] : true)) {
            $schema->inLanguage(str_replace('_','-', app()->getLocale()));
        }

        return $schema->toScript();
    }

    public static function responsiveImage(string $pathBase, array $sizes = [480, 768, 1024, 1600]): string
    {
        $srcset = collect($sizes)->map(function ($size) use ($pathBase) {
            return asset("images/{$pathBase}-{$size}.jpg") . " {$size}w";
        })->implode(', ');

        $fallback = asset("images/{$pathBase}-1024.jpg");

        return <<<HTML
<picture>
  <img src="{$fallback}" srcset="{$srcset}" sizes="(max-width: 768px) 100vw, 768px" alt="" loading="lazy">
</picture>
HTML;
    }

    public static function responsiveVideo(string $videoBaseName, array $formats = ['mp4', 'webm'], string $poster = null): string
    {
        $sources = collect($formats)->map(function ($format) use ($videoBaseName) {
            return '<source src="'.asset("videos/{$videoBaseName}.{$format}").'" type="video/'.$format.'">';
        })->implode("\n");

        return <<<HTML
<video controls preload="metadata" poster="{$poster}">
  {$sources}
  Twoja przeglądarka nie wspiera odtwarzacza wideo.
</video>
HTML;
    }
}
