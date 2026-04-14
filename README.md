# Dominservice Laravel Theme Helper

[![Packagist](https://img.shields.io/packagist/v/dominservice/laravel-theme-helper.svg)]()
[![Latest Version](https://img.shields.io/github/release/dominservice/laravel-theme-helper.svg?style=flat-square)](https://github.com/dominservice/laravel-theme-helper/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/dominservice/laravel-theme-helper.svg?style=flat-square)](https://packagist.org/packages/dominservice/laravel-theme-helper)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

A Laravel package for:

- SEO and meta management,
- asset registration and rendering,
- breadcrumb and hreflang support,
- JSON-LD / Schema.org generation,
- sitemap and `robots.txt` generation helpers,
- responsive image rendering with `picture` / AVIF / WebP / fallback support,
- schema presets for common company and CMS pages,
- theme registry and active theme selection,
- public/admin theme context handling,
- asset resolution for `mix`, `vite`, and static builds,
- theme discovery through `theme.json` manifests.

It is designed to be the integration layer between your Laravel application and one or more frontend themes, while still providing the usual `<head>` and `</body>` helpers.

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Theme system](#theme-system)
  - [Contexts](#contexts)
  - [Theme config](#theme-config)
  - [Theme discovery with `theme.json`](#theme-discovery-with-themejson)
  - [Drivers](#drivers)
  - [Theme switching](#theme-switching)
- [Structured data](#structured-data)
  - [Multiple schema types on one page](#multiple-schema-types-on-one-page)
  - [Schema collection API](#schema-collection-api)
  - [Schema presets](#schema-presets)
- [Sitemap](#sitemap)
- [Robots.txt](#robotstxt)
- [Responsive images](#responsive-images)
- [API](#api)
- [Blade integration](#blade-integration)
- [Helpers](#helpers)
- [Deployment notes](#deployment-notes)
- [Testing](#testing)
- [License](#license)

---

## Requirements

- PHP 8.1+
- Laravel 10.x / 11.x / 12.x / 13.x
- `spatie/schema-org`
- Optional: `mcamara/laravel-localization`

---

## Installation

```bash
composer require dominservice/laravel-theme-helper
```

Optional hreflang integration:

```bash
composer require mcamara/laravel-localization
```

Publish config if you want to define themes globally:

```bash
php artisan vendor:publish --tag=config
```

This publishes:

- `config/theme-helper.php`

---

## Quick start

```php
use Dominservice\LaravelThemeHelper\Facades\Theme;

Theme::meta()
    ->setTitle('DSO-IT', config('app.name'))
    ->setDescription('Software house and custom web systems')
    ->setCanonical(url()->current())
    ->setAuthor('Domin Software')
    ->setGenerator('DomiPress')
    ->setHttpEquiv('X-UA-Compatible', 'IE=edge')
    ->setIcons([
        'favicon' => asset('favicon.ico'),
        'manifest' => asset('site.webmanifest'),
        'theme_color' => '#0f172a',
    ]);

Theme::assets()->useTheme('public');

echo Theme::renderHead();
echo Theme::renderBodyEnd();
```

`setIcons()` wspiera także pełniejsze deklaracje favicon/PWA, m.in. `apple_touch`, `icon`, `manifest`, `mask_icon`, `mask_color`, `ms_tile_image`, `ms_tile_color` i `theme_color`.
`MetaManager` wspiera też dodatkowe tagi head przez `setAuthor()`, `setGenerator()`, `addMeta()` i `setHttpEquiv()`.

Switch public theme dynamically:

```php
Theme::useTheme('public-metronic', 'public');
Theme::assets()->useTheme('public');
```

---

## Theme system

### Contexts

The package supports theme contexts.

Typical usage:

- `admin`
- `public`

Rules are application-defined, but a common setup is:

- admin always uses a fixed admin theme
- public can use one of many declared themes

### Theme config

Published config structure:

```php
return [
    'contexts' => [
        'admin' => [
            'default_theme' => 'admin',
        ],
        'public' => [
            'default_theme' => 'public',
        ],
    ],

    'themes' => [
        'admin' => [
            'label' => 'Admin',
            'context' => 'admin',
            'driver' => 'static',
            'layout' => null,
            'view_path' => null,
            'entries' => [
                'css' => [],
                'js' => [],
            ],
            'meta' => [],
        ],
    ],

    'discovery' => [
        'enabled' => false,
        'manifest_filename' => 'theme.json',
        'scan_paths' => [],
    ],
];
```

### Theme discovery with `theme.json`

The package can discover themes from one or more directories. This lets you keep your application-defined themes in config while also supporting uploaded or shipped themes described by a manifest.

Example config:

```php
'discovery' => [
    'enabled' => true,
    'manifest_filename' => 'theme.json',
    'scan_paths' => [
        resource_path('themes'),
        base_path('themes'),
    ],
],
```

Example `theme.json`:

```json
{
  "key": "classic",
  "label": "Classic",
  "context": "public",
  "driver": "mix",
  "layout": "themes.classic.layouts.app",
  "view_path": "resources/views/themes/classic",
  "manifest_path": "public/themes/classic/mix-manifest.json",
  "deploy_target": "public/themes/classic",
  "entries": {
    "css": ["themes/classic/app.css"],
    "js": ["themes/classic/app.js"]
  },
  "description": "Base public theme",
  "version": "1.0.0",
  "author": {
    "name": "Domin Software"
  },
  "settings": {
    "supports_upload": true
  }
}
```

If a theme key already exists in config, config has priority over discovered manifests. This makes discovery safe for local overrides.

### Drivers

Supported asset drivers:

- `static`
- `mix`
- `vite`

### Theme switching

```php
Theme::useTheme('public-metronic', 'public');
Theme::useTheme('admin', 'admin');

$theme = Theme::currentTheme('public');
$layout = $theme->layout();
$driver = $theme->driver();
$description = $theme->description();
$previewImage = $theme->previewImage();
```

---

## Structured data

### Multiple schema types on one page

Google supports multiple structured data items on the same page when they describe user-visible content on that page. This is useful for pages that need more than one schema type at once, for example:

- `Organization`
- `WebSite`
- `WebPage`
- `BreadcrumbList`
- `Product`
- `Service`
- `FAQPage`

This package supports that pattern through `@graph`.

Simple example:

```php
echo Theme::structuredGraph([
    [
        '@type' => 'Organization',
        'name' => 'Domin Software',
        'url' => 'https://example.com',
    ],
    [
        '@type' => 'WebPage',
        'name' => 'CRM systems',
        'url' => 'https://example.com/crm',
    ],
    [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => 'https://example.com',
            ],
        ],
    ],
]);
```

### Schema collection API

```php
$schemas = Theme::schemaCollection()
    ->organization([
        'name' => 'Domin Software',
        'url' => 'https://example.com',
    ])
    ->product([
        'name' => 'CMS Starter',
    ])
    ->service([
        'name' => 'CRM implementation',
        'serviceType' => 'Custom CRM development',
    ]);

echo Theme::structuredGraph($schemas);
```

This lets you output one graph that contains separate entities for organization data, product data, services, breadcrumbs or page-level metadata.

### Schema presets

For common business pages you often want a repeatable graph instead of building every entity manually.

Available preset keys:

- `organization`
- `website`
- `home_page`
- `web_page`
- `service_page`
- `product_page`
- `faq_page`

Example:

```php
$schemas = Theme::schemaPreset('service_page', [
    'organization' => [
        'name' => 'Domin Software',
        'url' => 'https://example.com',
        'logo' => 'https://example.com/logo.png',
    ],
    'web_page' => [
        'name' => 'CRM systems',
        'url' => 'https://example.com/crm',
        'description' => 'Custom CRM systems for companies',
    ],
    'service' => [
        'name' => 'CRM implementation',
        'serviceType' => 'Custom CRM development',
    ],
    'breadcrumbs' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://example.com'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'CRM', 'item' => 'https://example.com/crm'],
    ],
]);

echo Theme::structuredGraph($schemas);
```

---

## Sitemap

The package includes a builder for XML sitemaps. It supports:

- standard `urlset`,
- `sitemapindex`,
- image sitemap extensions,
- alternate locale URLs through `xhtml:link`,
- configurable default `changefreq` and `priority`.

Example:

```php
$xml = Theme::sitemap()
    ->addUrl('https://example.com/', [
        'lastmod' => now(),
        'changefreq' => 'daily',
        'priority' => '1.0',
        'alternates' => [
            ['hreflang' => 'pl-PL', 'href' => 'https://example.com/'],
            ['hreflang' => 'en-US', 'href' => 'https://example.com/en'],
        ],
        'images' => [
            [
                'loc' => 'https://example.com/storage/hero.webp',
                'title' => 'Homepage hero image',
            ],
        ],
    ])
    ->renderUrlset();
```

For sitemap indexes:

```php
$index = Theme::sitemap()
    ->addSitemap('https://example.com/sitemap-pages.xml')
    ->addSitemap('https://example.com/sitemap-blog.xml')
    ->renderIndex();
```

---

## Robots.txt

The package also includes a builder for `robots.txt`.

Example:

```php
$robots = Theme::robots()
    ->addGroup('*', allow: ['/'], disallow: ['/admin', '/login'])
    ->addSitemap('https://example.com/sitemap.xml')
    ->setHost('example.com')
    ->render();
```

---

## Responsive images

The package includes a small renderer for SEO-friendly responsive images with modern formats.

Example using explicit `<source>` nodes:

```php
echo Theme::images()->picture([
    [
        'type' => 'image/avif',
        'srcset' => asset('themes/classic/img/about.avif'),
        'media' => '(min-width: 768px)',
    ],
    [
        'type' => 'image/webp',
        'srcset' => asset('themes/classic/img/about.webp'),
    ],
], [
    'src' => asset('themes/classic/img/about.jpg'),
    'alt' => 'About section visual',
    'width' => 1400,
    'height' => 1040,
]);
```

Or using a simpler format map:

```php
echo Theme::images()->pictureFromFormats([
    'avif' => asset('themes/classic/img/about.avif'),
    'webp' => asset('themes/classic/img/about.webp'),
    'jpg' => asset('themes/classic/img/about.jpg'),
], [
    'alt' => 'About section visual',
    'width' => 1400,
    'height' => 1040,
]);
```

---

## API

Main facade methods:

```php
Theme::meta();
Theme::assets();
Theme::breadcrumbs();
Theme::hreflang();
Theme::sitemap();
Theme::robots();
Theme::schemaPreset('home_page', [...]);
Theme::images();
Theme::registry();
Theme::contexts();
Theme::currentTheme('public');
Theme::useTheme('classic', 'public');
Theme::structured([...]);
Theme::schemaCollection();
Theme::structuredGraph([...]);
Theme::renderHead();
Theme::renderBodyEnd();
```

---

## Blade integration

```blade
<head>
    {!! Theme::renderHead() !!}
</head>
<body>
    @yield('content')

    {!! Theme::renderBodyEnd() !!}
</body>
```

---

## Helpers

Available helpers:

- `theme_structured_data(array $data, array $options = [])`
- `theme_structured_graph(array $schemas, array $options = [])`
- `theme_sitemap()`
- `theme_robots()`
- `theme_schema_preset(string $preset, array $data = [])`
- `theme_picture(array $sources, array $imgAttributes = [], array $pictureAttributes = [])`
- `theme_current_theme(string $context = 'public')`
- `theme_current_layout(string $context = 'public')`
- `theme_use(string $themeKey, ?string $context = null)`

---

## Deployment notes

Theme definitions can now also carry:

- `build_config_path`
- `manifest_path`
- `hot_file_path`
- `deploy_target`
- `source_path`
- `preview_image`
- `settings`

This makes the package a good base for future theme upload, import/export and theme management panels without forcing a specific storage model today.

---

## Testing

Run package tests:

```bash
vendor/bin/phpunit
```

---

## License

MIT
