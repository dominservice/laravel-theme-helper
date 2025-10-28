<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Localization;

final class Hreflang
{
    /** @var array<int,array{href:string,lang:string}> */
    private array $alternates = [];

    public function clear(): self
    {
        $this->alternates = [];
        return $this;
    }

    /**
     * Automatyczne uzupełnienie hreflang:
     * 1) Najpierw próbuje Dominservice\DataLocaleParser\DataParser + route_locale()
     * 2) Fallback: Mcamara\LaravelLocalization (jeśli dostępny)
     */
    public function auto(?string $currentUrl = null): self
    {
        $this->clear();

        if (\class_exists(\Dominservice\DataLocaleParser\DataParser::class)) {
            return $this->autoFromDataLocaleParser();
        }

        if (\class_exists(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::class)) {
            return $this->autoFromLaravelLocalization($currentUrl);
        }

        return $this; // brak integracji – można dodać alternatywy ręcznie addAlternate()
    }

    /**
     * Integracja z Dominservice\DataLocaleParser\DataParser
     * Wymaga dostępności helpera route_locale().
     */
    public function autoFromDataLocaleParser(): self
    {
        $this->alternates = [];

        // Bezpiecznie pobierz parametry aktualnej trasy:
        $parameters = request()->route()?->parameters() ?? [];
        $routeName  = request()->route()?->getName();

        // Jeśli brak nazwy trasy – nie zrobimy poprawnych URL-i per język
        if (!$routeName) {
            return $this;
        }

        $allowed = \config('data_locale_parser.allowed_locales');
        $parser  = new \Dominservice\DataLocaleParser\DataParser();

        foreach ($parser->getLanguagesFullData($allowed) as $localeCode => $localeData) {
            // route_locale($locale, $routeName, $parameters)
            $href = \route_locale($localeCode, $routeName, $parameters);
            if ($href) {
                $this->alternates[] = [
                    'href' => $href,
                    'lang' => \str_replace('_', '-', (string)$localeCode),
                ];
            }
        }

        // x-default na fallback_locale
        $fallback = \config('app.fallback_locale');
        if ($fallback && \function_exists('route_locale')) {
            $xd = \route_locale($fallback, $routeName, $parameters);
            if ($xd) {
                $this->alternates[] = [
                    'href' => $xd,
                    'lang' => 'x-default',
                ];
            }
        }

        return $this;
    }

    /**
     * Integracja z mcamara/laravel-localization
     */
    public function autoFromLaravelLocalization(?string $currentUrl = null): self
    {
        $this->alternates = [];

        if (!\class_exists(\Mcamara\LaravelLocalization\Facades\LaravelLocalization::class)) {
            return $this;
        }

        $curr = $currentUrl ?: \url()->current();

        foreach (\Mcamara\LaravelLocalization\Facades\LaravelLocalization::getSupportedLocales() as $localeCode => $localeData) {
            $href = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($localeCode, $curr, [], true);
            if ($href) {
                $this->alternates[] = [
                    'href' => $href,
                    'lang' => \str_replace('_', '-', (string)$localeCode),
                ];
            }
        }

        // x-default na fallback_locale
        $fallback = \config('app.fallback_locale');
        if ($fallback) {
            $xd = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL($fallback, $curr, [], true);
            if ($xd) {
                $this->alternates[] = [
                    'href' => $xd,
                    'lang' => 'x-default',
                ];
            }
        }

        return $this;
    }

    public function addAlternate(string $href, string $lang): self
    {
        $this->alternates[] = ['href' => $href, 'lang' => $lang];
        return $this;
    }

    public function renderAlternates(): string
    {
        if (empty($this->alternates)) {
            return '';
        }
        $out = [];
        foreach ($this->alternates as $a) {
            $out[] = '<link rel="alternate" hreflang="'.e($a['lang']).'" href="'.e($a['href']).'">';
        }
        return \implode("\n", $out);
    }
}
