<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Assets;

use Dominservice\LaravelThemeHelper\Support\Assets\Resolvers\AssetResolverInterface;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeContextManager;
use Dominservice\LaravelThemeHelper\Support\Themes\ThemeRegistry;

final class AssetManager
{
    /** @var array<int,array{href:string,rel:string,as?:string,crossorigin?:string,media?:string}> */
    private array $headLinks = [];

    /** @var array<int,array{href:string,async?:bool,defer?:bool,attrs?:array<string,string>}> */
    private array $scripts = [];

    /** @var array<int,string> */
    private array $inlineCss = [];

    /** @var array<int,string> */
    private array $inlineJs = [];

    private ?ThemeRegistry $themeRegistry = null;

    private ?ThemeContextManager $themeContexts = null;

    /** @var array<string,AssetResolverInterface> */
    private array $themeResolvers = [];

    /** @var array<int,string> */
    private array $enabledThemeContexts = [];

    public function setThemeSupport(
        ThemeRegistry $registry,
        ThemeContextManager $contexts,
        array $resolvers,
    ): self {
        $this->themeRegistry = $registry;
        $this->themeContexts = $contexts;
        $this->themeResolvers = $resolvers;

        return $this;
    }

    public function useTheme(string $context = 'public'): self
    {
        if (!in_array($context, $this->enabledThemeContexts, true)) {
            $this->enabledThemeContexts[] = $context;
        }

        return $this;
    }

    /* ===== register ===== */

    public function addStylesheet(string $href, ?string $media = null): self
    {
        $item = ['href' => $href, 'rel' => 'stylesheet'];
        if ($media) $item['media'] = $media;
        $this->headLinks[] = $item;
        return $this;
    }

    public function preload(string $href, string $as, ?string $crossorigin = null): self
    {
        $item = ['href' => $href, 'rel' => 'preload', 'as' => $as];
        if ($crossorigin) $item['crossorigin'] = $crossorigin;
        $this->headLinks[] = $item;
        return $this;
    }

    public function prefetch(string $href): self
    {
        $this->headLinks[] = ['href' => $href, 'rel' => 'prefetch'];
        return $this;
    }

    public function dnsPrefetch(string $host): self
    {
        $this->headLinks[] = ['href' => $host, 'rel' => 'dns-prefetch'];
        return $this;
    }

    public function addScript(string $src, bool $defer = true, bool $async = false, array $attrs = []): self
    {
        $this->scripts[] = ['href' => $src, 'defer' => $defer, 'async' => $async, 'attrs' => $attrs];
        return $this;
    }

    public function inlineCss(string $css): self
    {
        $this->inlineCss[] = trim($css);
        return $this;
    }

    public function inlineJs(string $js): self
    {
        $this->inlineJs[] = trim($js);
        return $this;
    }

    /* ===== render ===== */

    public function renderHeadLinks(): string
    {
        $out = [];
        foreach ($this->uniqueAssetItems(array_merge($this->resolveThemeAssets()['links'], $this->headLinks)) as $l) {
            $rel = e($l['rel']);
            $href = e($l['href']);
            $attrs = ['rel="'.$rel.'"', 'href="'.$href.'"'];
            if (!empty($l['as']))         $attrs[] = 'as="'.e($l['as']).'"';
            if (!empty($l['crossorigin']))$attrs[] = 'crossorigin="'.e($l['crossorigin']).'"';
            if (!empty($l['media']))      $attrs[] = 'media="'.e($l['media']).'"';
            $out[] = '<link '.implode(' ', $attrs).'>';
        }
        if (!empty($this->inlineCss)) {
            $out[] = "<style>\n".implode("\n", $this->inlineCss)."\n</style>";
        }
        return implode("\n", $out);
    }

    public function renderBodyScripts(): string
    {
        $out = [];
        foreach ($this->uniqueAssetItems(array_merge($this->resolveThemeAssets()['scripts'], $this->scripts)) as $s) {
            $attrs = ['src="'.e($s['href']).'"'];
            if (!empty($s['defer'])) $attrs[] = 'defer';
            if (!empty($s['async'])) $attrs[] = 'async';
            foreach (($s['attrs'] ?? []) as $k => $v) {
                $attrs[] = e($k).'="'.e($v).'"';
            }
            $out[] = '<script '.implode(' ', $attrs).'></script>';
        }
        if (!empty($this->inlineJs)) {
            $out[] = "<script>\n".implode("\n", $this->inlineJs)."\n</script>";
        }
        return implode("\n", $out);
    }

    /** @return array{links: array<int,array<string,mixed>>, scripts: array<int,array<string,mixed>>} */
    private function resolveThemeAssets(): array
    {
        $assets = ['links' => [], 'scripts' => []];
        if ($this->themeRegistry === null || $this->themeContexts === null) {
            return $assets;
        }

        foreach ($this->enabledThemeContexts as $context) {
            $theme = $this->themeContexts->current($context);
            $resolver = $this->themeResolvers[$theme->driver()] ?? null;
            if ($resolver === null) {
                continue;
            }

            $resolved = $resolver->resolve($theme);
            $assets['links'] = array_merge($assets['links'], $resolved['links']);
            $assets['scripts'] = array_merge($assets['scripts'], $resolved['scripts']);
        }

        return $assets;
    }

    /** @param array<int,array<string,mixed>> $items
     *  @return array<int,array<string,mixed>>
     */
    private function uniqueAssetItems(array $items): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $key = md5((string) json_encode($item));
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $item;
        }

        return $unique;
    }
}
