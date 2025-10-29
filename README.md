# Dominservice Laravel Theme Helper

A small Laravel utility to manage everything you usually put into the <head> and the closing of <body>, plus a pragmatic Schema.org JSON‑LD generator.

It provides a consistent API for:
- Meta tags (title/description/keywords/robots, OpenGraph, Twitter Cards, canonical, prev/next)
- Asset links and scripts (CSS/JS; preload/prefetch/dns‑prefetch; inline CSS/JS)
- Breadcrumbs (HTML + JSON‑LD)
- Hreflang alternates
- Structured Data in a hybrid mode: Spatie Schema.org builder with RAW fallback and light validation

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Auto‑discovery and aliases](#auto-discovery-and-aliases)
- [Quick start](#quick-start)
- [API and usage](#api-and-usage)
  - [Theme manager (Theme facade)](#theme-manager-theme-facade)
  - [MetaManager](#metamanager)
  - [AssetManager](#assetmanager)
  - [Breadcrumbs](#breadcrumbs)
  - [Hreflang](#hreflang)
  - [Structured Data (ThemeStructuredData)](#structured-data-themestructureddata)
  - [Schema Types constants](#schema-types-constants)
- [Validation, policies and errors](#validation-policies-and-errors)
- [Blade integration](#blade-integration)
- [Testing](#testing)
- [FAQ](#faq)
- [License](#license)

---

## Requirements

- PHP 8.1+
- Laravel 10.x / 11.x / 12.x
- spatie/schema-org (used for rich JSON‑LD building; the package depends on it)
- Optional: mcamara/laravel-localization (for automatic hreflang generation; you can always add alternates manually)

---

## Installation

Install via Composer:
```bash
composer require dominservice/laravel-theme-helper
```

If you want automatic hreflang detection via LaravelLocalization:
```bash
composer require mcamara/laravel-localization
```

---

## Auto‑discovery and aliases

Laravel will automatically register the Service Provider and facade. If you prefer manual setup, add to your config/app.php:

```php
'providers' => [
    // ...
    Dominservice\LaravelThemeHelper\ServiceProvider::class,
],
'aliases' => [
    // ...
    'Theme' => Dominservice\LaravelThemeHelper\Facades\Theme::class,
],
```

Namespace: Dominservice\LaravelThemeHelper (PSR‑4 → src/)

---

## Quick start

```php
use Dominservice\LaravelThemeHelper\Facades\Theme;

// META
Theme::meta()
    ->setTitle('EcoChatka 70', 'Dominwise')
    ->setDescription('Modular strawbale house, 70 m²')
    ->setCanonical(url()->current())
    ->setOg(['image' => asset('og.jpg')]);

// ASSETS
Theme::assets()
    ->preload(asset('css/app.css'), 'style')
    ->addStylesheet(asset('css/app.css'))
    ->addScript(asset('js/app.js'), defer: true);

// HREFLANG
Theme::hreflang()->autoFromLaravelLocalization();

// HEAD + BODY
echo Theme::renderHead();
// ... page content ...
echo Theme::renderBodyEnd();
```

Breadcrumbs (HTML + JSON‑LD):
```php
$items = [
  ['name' => 'Home', 'url' => url('/')],
  ['name' => 'Category', 'url' => url('/category')],
  ['name' => 'Product'],
];

echo Theme::breadcrumbs()->render($items);
echo Theme::breadcrumbJsonLd($items);
```

---

## API and usage

### Theme manager (Theme facade)

The container service that wires everything together:

- Theme::meta(): MetaManager
- Theme::assets(): AssetManager
- Theme::breadcrumbs(): Breadcrumbs
- Theme::hreflang(): Hreflang
- Theme::structured(array $data, array $options = []): string
- Theme::structuredErrors(): array
- Theme::breadcrumbJsonLd(array $items, array $options = []): string
- Theme::renderHead(): string
- Theme::renderBodyEnd(): string

Global options for structured():
- use_spatie: bool = true — use Spatie builder when available (the package requires it)
- on_invalid: 'skip'|'error' = 'skip' — validation policy
- attach_inLanguage: bool = true — auto fill inLanguage using app()->getLocale()

---

### MetaManager

Configure and render meta tags (title/description/keywords/robots), canonical, prev/next, OG and Twitter, icons/manifest, and optional CSRF meta.

```php
Theme::meta()
  ->setTitle('Title', 'Site Name')
  ->setDescription('Page description')
  ->setKeywords('phrase1, phrase2')
  ->setRobots('index,follow')
  ->setCanonical(url()->current())
  ->setPrev($prevUrl)
  ->setNext($nextUrl)
  ->setOg([
      'type' => 'article',
      'image' => asset('og.jpg'),
  ])
  ->setTwitter([
      'card'  => 'summary_large_image',
      'site'  => '@your_profile',
      'image' => asset('tw.jpg'),
  ])
  ->setIcons([
      // Backward‑compat single favicon
      'favicon'     => '/favicon.ico',

      // Apple touch icon(s)
      // either array of strings or array of arrays with sizes
      'apple_touch' => [
          '/apple-touch-icon.png',
          // ['href' => '/apple-touch-icon-57x57.png', 'sizes' => '57x57'],
      ],

      // Standard icons with sizes/type (optional)
      'icon'        => [
          // '/favicon-32x32.png',
          // ['href' => '/favicon-32x32.png', 'sizes' => '32x32', 'type' => 'image/png'],
      ],

      // PWA manifest
      'manifest'    => '/site.webmanifest',

      // Safari mask icon (optional)
      'mask_icon'   => '/safari-pinned-tab.svg',
      'mask_color'  => '#000000',

      // Microsoft tiles (optional)
      'ms_tile_color' => '#ffffff',
      'ms_tile_image' => '/mstile-150x150.png',
    
      // Theme color (optional)
      'theme_color' => '#ffffff',
  ]);

echo Theme::meta()->renderStandard();
```

CSRF/XSRF token meta in <head>:
```php
// Adds: <meta name="csrf-token" content="..."> to the <head>
Theme::meta()->withCsrf();

// Optionally provide your own token value (e.g., from Sanctum or custom guard):
Theme::meta()->withCsrf(true, $token);
// or later
Theme::meta()->setCsrfToken($token);
```

Notes:
- withCsrf() tries to use Laravel's csrf_token() helper. If the session is not started or the helper is unavailable, nothing is rendered unless you set a token explicitly.
- Many front-end libraries (Axios, Laravel Echo) read this meta to send the X-CSRF-TOKEN header automatically.

Defaults:
- title falls back to config('app.name')
- og:url → canonical or url()->current()
- og:locale → app()->getLocale() (e.g. en-US)
- twitter:card → summary_large_image

---

### AssetManager

Register and render links for <head> and scripts for the end of <body>:

```php
Theme::assets()
  ->dnsPrefetch('//fonts.googleapis.com')
  ->preload(asset('css/app.css'), 'style')
  ->addStylesheet(asset('css/app.css'), media: 'all')
  ->inlineCss('.hero{display:grid;place-items:center;}')

  ->addScript(asset('js/app.js'), defer: true, async: false, attrs: ['data-app'=>'1'])
  ->inlineJs('window.__boot = true;');

echo Theme::assets()->renderHeadLinks();  // into <head>
echo Theme::assets()->renderBodyScripts(); // before </body>
```

Supported rel values: stylesheet, preload (with as=...), prefetch, dns-prefetch
Scripts: async, defer, arbitrary attributes via attrs
Inline: inlineCss(), inlineJs()

---

### Breadcrumbs

Normalize your items for JSON‑LD and render a simple HTML list.

```php
$items = [
  ['name'=>'Home', 'url'=>url('/')],
  ['name'=>'Category', 'url'=>url('/category')],
  ['name'=>'Product'],
];

// HTML
echo Theme::breadcrumbs()->render($items);

// JSON-LD
echo Theme::breadcrumbJsonLd($items);
```

---

### Hreflang

Automatic generation using LaravelLocalization or manual alternates.

```php
// Automatic (if mcamara/laravel-localization is installed)
Theme::hreflang()->autoFromLaravelLocalization();

// Manual
Theme::hreflang()
  ->addAlternate('https://example.com/en', 'en-US')
  ->addAlternate('https://example.com/de', 'de-DE');

echo Theme::hreflang()->renderAlternates();
```

---

### Structured Data (ThemeStructuredData)

Hybrid JSON‑LD engine: try Spatie builder first, then fallback to permissive RAW mode.

#### RAW @graph mode (full freedom, including future types/properties)
```php
echo Theme::structured([
  'schemas' => [
    ['@type'=>'Organization','name'=>'Dominwise','url'=>config('app.url')],
    ['@type'=>'WebSite','url'=>config('app.url'),'name'=>config('app.name')],
  ],
], ['on_invalid' => 'skip']);
```

#### Shortcuts for popular types
```php
use Dominservice\LaravelThemeHelper\Schema\Types;

echo Theme::structured([
  'type'  => Types::PRODUCT,
  'name'  => 'EcoChatka 70',
  'image' => [asset('img.jpg')],
  'offers'=> [['price'=>'199000','priceCurrency'=>'PLN','availability'=>'https://schema.org/InStock','url'=>url()->current()]],
  'aggregateRating' => ['ratingValue'=>4.8,'reviewCount'=>37],
], ['use_spatie'=>true, 'on_invalid'=>'error']);
```

Supported type shortcuts (RAW + Spatie, depending on availability):
Organization, LocalBusiness, Person, Product, Article, NewsArticle, BlogPosting, BreadcrumbList, Event, Recipe, VideoObject, FAQPage, HowTo, WebSite, WebPage, SoftwareApplication, JobPosting, Course, Review, ItemList, ImageObject, AudioObject, Book, Dataset, Service

Minimal validation (a practical subset of common Rich Results requirements):
- Product → name, offers[*].{price,priceCurrency}
- Article/NewsArticle/BlogPosting → headline
- BreadcrumbList → itemListElement
- Event → name, startDate, location
- FAQPage → mainEntity (Question/Answer)
- HowTo → name, step
- LocalBusiness → name, address
- VideoObject → name, thumbnailUrl, uploadDate
- Recipe → name, recipeIngredient, recipeInstructions
- SoftwareApplication → name, operatingSystem
- JobPosting → title, hiringOrganization, jobLocation
- ItemList → itemListElement
- ImageObject → contentUrl
- WebSite → url, name
- WebPage → url, name
- Organization → name

Formats:
- URL: url, contentUrl, embedUrl, logo
- ISO8601 date/datetime: datePublished, dateModified, startDate, endDate, uploadDate, priceValidUntil
- ISO8601 duration: duration (VideoObject)
- Enums: Offer.availability, Offer.itemCondition (official schema.org URL enums or any valid URL)

Policies:
- on_invalid = 'skip' — the parameter is ignored and added to the error buffer
- on_invalid = 'error' — throws InvalidArgumentException

Validation errors buffer:
```php
$html = Theme::structured($data, ['on_invalid'=>'skip']);
$errors = Theme::structuredErrors(); // array of messages
```

---

## Schema Types constants

Import useful constants from Dominservice\LaravelThemeHelper\Schema\Types when building shortcuts. Examples include: ORGANIZATION, PRODUCT, ARTICLE, BREADCRUMB, WEBSITE, WEBPAGE, VIDEO_OBJECT, FAQ_PAGE, HOW_TO, LOCAL_BUSINESS, RECIPE, SOFTWARE_APPLICATION, JOB_POSTING, ITEM_LIST, IMAGE_OBJECT, AUDIO_OBJECT, BOOK, DATASET, SERVICE — and more as provided in the package.

---

## Validation, policies and errors

- Validation is intentionally light: it focuses on common Rich Results requirements and basic format checks.
- It does not block future fields/types (especially in RAW @graph mode).
- With on_invalid = 'skip' all diagnostics are collected and accessible via Theme::structuredErrors().

---

## Blade integration

Use the ready‑made renderers in your layout:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<head>
    {!! Theme::renderHead() !!}
</head>
<body>
    @yield('content')
    {!! Theme::renderBodyEnd() !!}
</body>
```

Set page values inside views or controllers:

```blade
@php
Theme::meta()
    ->setTitle($title ?? 'Page')
    ->setDescription($description ?? null)
    ->setCanonical(url()->current());
@endphp
```

You can also use the global helper for Structured Data:

```php
// helpers.php
theme_structured_data([
    'type' => \Dominservice\LaravelThemeHelper\Schema\Types::ORGANIZATION,
    'name' => config('app.name'),
]);
```

---

## Testing

This is a library, so it ships without a full application skeleton. In your host app:

```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit
```

A good starting point is to add integration tests that assert the generated HTML/JSON‑LD and the behavior of validation policies (on_invalid).

---

## FAQ

1) Is Spatie required?  
Yes. The package depends on spatie/schema-org and uses it whenever possible, while still allowing a permissive RAW fallback and extra format checks.

2) Is LaravelLocalization required?  
No. You can add hreflang alternates manually via addAlternate(). If the package is present, autoFromLaravelLocalization() will pick them up.

3) Does the package impose a project structure?  
No. It exposes services and a facade. Use only the modules you need.

---

## License

MIT © Dominservice
