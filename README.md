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
- theme registry and active theme selection,
- public/admin theme context handling,
- asset resolution for `mix`, `vite`, and static builds.

It is designed to be the integration layer between your Laravel application and one or more frontend themes, while still providing the usual `<head>` and `</body>` helpers.

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Theme system](#theme-system)
  - [Contexts](#contexts)
  - [Theme config](#theme-config)
  - [Drivers](#drivers)
  - [Theme switching](#theme-switching)
- [API](#api)
  - [Theme manager](#theme-manager)
  - [MetaManager](#metamanager)
  - [AssetManager](#assetmanager)
  - [Breadcrumbs](#breadcrumbs)
  - [Hreflang](#hreflang)
  - [Structured data](#structured-data)
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
    ->setCanonical(url()->current());

Theme::assets()
    ->useTheme('public');

echo Theme::renderHead();
echo Theme::renderBodyEnd();
```

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
            'build_config_path' => null,
            'manifest_path' => null,
            'hot_file_path' => null,
            'dev_server_url' => null,
            'public_path' => null,
            'asset_url' => null,
            'deploy_target' => null,
            'entries' => [
                'css' => [],
                'js' => [],
            ],
        ],
        'public-metronic' => [
            'label' => 'Public Metronic',
            'context' => 'public',
            'driver' => 'vite',
            'layout' => 'themes.public-metronic.layouts.app',
            'build_config_path' => base_path('themes/public-metronic/vite.config.js'),
            'manifest_path' => public_path('themes/public-metronic/.vite/manifest.json'),
            'hot_file_path' => base_path('themes/public-metronic/public.hot'),
            'dev_server_url' => env('PUBLIC_METRONIC_VITE_DEV_SERVER'),
            'asset_url' => env('APP_URL') . '/themes/public-metronic',
            'deploy_target' => public_path('themes/public-metronic'),
            'entries' => [
                'css' => [],
                'js' => [
                    'resources/app.js',
                ],
            ],
        ],
    ],
];
```

### Drivers

Supported asset drivers:

- `static`
- `mix`
- `vite`

#### `static`

Use for pre-resolved assets or manually declared URLs:

```php
'driver' => 'static',
'entries' => [
    'css' => ['assets/css/app.css'],
    'js' => ['assets/js/app.js'],
],
```

#### `mix`

Use when the theme has its own `mix-manifest.json`:

```php
'driver' => 'mix',
'manifest_path' => public_path('themes/public-alt/mix-manifest.json'),
'entries' => [
    'css' => ['themes/public-alt/app.css'],
    'js' => ['themes/public-alt/app.js'],
],
```

#### `vite`

Use when the theme has its own Vite manifest and optionally a hot file:

```php
'driver' => 'vite',
'manifest_path' => public_path('themes/public-metronic/.vite/manifest.json'),
'hot_file_path' => base_path('themes/public-metronic/public.hot'),
'dev_server_url' => env('PUBLIC_METRONIC_VITE_DEV_SERVER'),
'entries' => [
    'js' => ['resources/app.js'],
],
```

If `hot_file_path` exists and contains a dev-server URL, the package will render dev assets with `@vite/client`.

### Theme switching

Switching themes:

```php
Theme::useTheme('public-metronic', 'public');
Theme::useTheme('admin', 'admin');
```

Reading current theme:

```php
$theme = Theme::currentTheme('public');
$layout = $theme->layout();
$driver = $theme->driver();
```

Registering themes at runtime:

```php
Theme::registry()->register('public-alt', [
    'label' => 'Public Alt',
    'context' => 'public',
    'driver' => 'mix',
    'manifest_path' => public_path('themes/public-alt/mix-manifest.json'),
    'entries' => [
        'css' => ['themes/public-alt/app.css'],
        'js' => ['themes/public-alt/app.js'],
    ],
]);
```

---

## API

### Theme manager

Main facade methods:

- `Theme::meta()`
- `Theme::assets()`
- `Theme::breadcrumbs()`
- `Theme::hreflang()`
- `Theme::structured(array $data, array $options = [])`
- `Theme::structuredErrors()`
- `Theme::breadcrumbJsonLd(array $items, array $options = [])`
- `Theme::registry()`
- `Theme::contexts()`
- `Theme::currentTheme(string $context = 'public')`
- `Theme::useTheme(string $themeKey, ?string $context = null)`
- `Theme::renderHead()`
- `Theme::renderBodyEnd()`

### MetaManager

Examples:

```php
Theme::meta()
    ->setTitle('Title', 'Site Name')
    ->setDescription('Page description')
    ->setKeywords('software house, laravel, cms')
    ->setRobots('index,follow')
    ->setCanonical(url()->current())
    ->setOg([
        'type' => 'website',
        'image' => asset('og.jpg'),
    ])
    ->setTwitter([
        'card' => 'summary_large_image',
    ]);
```

CSRF meta:

```php
Theme::meta()->withCsrf();
Theme::meta()->withCsrf(true, $token);
```

### AssetManager

Manual assets:

```php
Theme::assets()
    ->dnsPrefetch('//fonts.googleapis.com')
    ->preload(asset('css/app.css'), 'style')
    ->addStylesheet(asset('css/app.css'))
    ->addScript(asset('js/app.js'), defer: true)
    ->inlineCss('.hero{display:grid;}')
    ->inlineJs('window.__boot = true;');
```

Theme-driven assets:

```php
Theme::assets()->useTheme('public');
```

This will resolve CSS/JS using the currently active theme for that context.

Multiple contexts are supported:

```php
Theme::assets()
    ->useTheme('admin')
    ->useTheme('public');
```

### Breadcrumbs

```php
$items = [
    ['name' => 'Home', 'url' => url('/')],
    ['name' => 'Services', 'url' => url('/services')],
    ['name' => 'Laravel CMS'],
];

echo Theme::breadcrumbs()->render($items);
echo Theme::breadcrumbJsonLd($items);
```

### Hreflang

```php
Theme::hreflang()->autoFromLaravelLocalization();

Theme::hreflang()
    ->addAlternate('https://example.com/en', 'en-US')
    ->addAlternate('https://example.com/pl', 'pl-PL');
```

### Structured data

RAW graph mode:

```php
echo Theme::structured([
    'schemas' => [
        ['@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')],
        ['@type' => 'WebSite', 'name' => config('app.name'), 'url' => config('app.url')],
    ],
]);
```

Shortcut mode:

```php
use Dominservice\LaravelThemeHelper\Schema\Types;

echo Theme::structured([
    'type' => Types::SERVICE,
    'name' => 'Laravel CMS development',
]);
```

Validation errors:

```php
$html = Theme::structured($data, ['on_invalid' => 'skip']);
$errors = Theme::structuredErrors();
```

---

## Blade integration

Simple layout:

```blade
<head>
    {!! Theme::renderHead() !!}
</head>
<body>
    @yield('content')
    {!! Theme::renderBodyEnd() !!}
</body>
```

Theme-aware public layout:

```blade
@php
    Theme::assets()->useTheme('public');
    $publicTheme = Theme::currentTheme('public');
@endphp

@extends($publicTheme->layout() ?? 'layouts.app')
```

Controller example:

```php
use Dominservice\LaravelThemeHelper\Facades\Theme;

Theme::useTheme('public-metronic', 'public');
Theme::meta()
    ->setTitle('DSO-IT')
    ->setCanonical(url()->current());
```

---

## Helpers

Available helpers:

- `theme_structured_data(array $data, array $options = [])`
- `theme_current_theme(string $context = 'public')`
- `theme_current_layout(string $context = 'public')`
- `theme_use(string $themeKey, ?string $context = null)`

Examples:

```php
theme_use('public-metronic', 'public');

$theme = theme_current_theme('public');
$layout = theme_current_layout('public');
```

---

## Deployment notes

The package does not build assets by itself.

Instead, it stores enough metadata to let your application or deployment pipeline know:

- which theme is active,
- which build driver the theme uses,
- where the build config lives,
- where the manifest is expected,
- where the compiled assets are served from,
- where the deployment target should be.

Recommended usage:

- keep each public theme in its own directory,
- keep a separate `webpack.mix.js` or `vite.config.js` per theme,
- define the output path in the theme config,
- let deployment scripts build only the selected themes.

This package is intentionally a runtime integration layer, not a build runner.

---

## Testing

Run package tests as usual:

```bash
./vendor/bin/phpunit
```

Recommended additional tests in host applications:

- current theme resolution
- mix manifest resolution
- vite manifest and hot-file resolution
- admin/public layout switching
- render output for meta, hreflang and scripts

---

## License

MIT © Dominservice
