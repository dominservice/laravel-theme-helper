<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Meta;

final class MetaManager
{
    private ?string $title = null;
    private ?string $siteName = null;
    private ?string $description = null;
    private ?string $keywords = null;
    private ?string $canonical = null;
    private ?string $prev = null;
    private ?string $next = null;
    private ?string $robots = null;

    /** Open Graph */
    private array $og = [
        'type'     => 'website',
        'title'    => null,
        'site_name'=> null,
        'url'      => null,
        'image'    => null,
        'description' => null,
        'locale'   => null,
    ];

    /** Twitter Card */
    private array $twitter = [
        'card'        => 'summary_large_image',
        'site'        => null,
        'creator'     => null,
        'title'       => null,
        'description' => null,
        'image'       => null,
    ];

    /** Ikony / manifest / mask-icon */
    private array $icons = [
        'favicon'       => null, // e.g. /favicon.ico
        'apple_touch'   => [],   // [ '/apple-touch-icon.png', ... ]
        'manifest'      => null, // /site.webmanifest
        'mask_icon'     => null, // Safari pinned tab
        'mask_color'    => '#000000',
        'theme_color'   => null,
        'ms_tile_color' => null,
    ];

    /* ========== setters ========== */

    public function setTitle(?string $title, ?string $siteName = null): self
    {
        $this->title    = $title;
        $this->siteName = $siteName ?? $this->siteName ?? config('app.name');
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setKeywords(?string $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function setRobots(?string $robots): self
    {
        $this->robots = $robots;
        return $this;
    }

    public function setCanonical(?string $canonical): self
    {
        $this->canonical = $canonical;
        return $this;
    }

    public function setPrev(?string $prev): self
    {
        $this->prev = $prev;
        return $this;
    }

    public function setNext(?string $next): self
    {
        $this->next = $next;
        return $this;
    }

    public function setOg(array $props): self
    {
        $this->og = array_replace($this->og, $props);
        return $this;
    }

    public function setTwitter(array $props): self
    {
        $this->twitter = array_replace($this->twitter, $props);
        return $this;
    }

    public function setIcons(array $props): self
    {
        $this->icons = array_replace($this->icons, $props);
        return $this;
    }

    /* ========== render ========== */

    public function renderStandard(): string
    {
        $out = [];

        // <title>
        $title = $this->title ?? config('app.name');
        if ($title) {
            if ($this->siteName && $this->siteName !== $title) {
                $out[] = '<title>'.e(trim($title.' | '.$this->siteName)).'</title>';
            } else {
                $out[] = '<title>'.e($title).'</title>';
            }
        }

        // meta: description/keywords/robots
        if ($this->description) { $out[] = '<meta name="description" content="'.e($this->description).'">'; }
        if ($this->keywords)    { $out[] = '<meta name="keywords" content="'.e($this->keywords).'">'; }
        if ($this->robots)      { $out[] = '<meta name="robots" content="'.e($this->robots).'">'; }

        // canonical + prev/next
        if ($this->canonical) { $out[] = '<link rel="canonical" href="'.e($this->canonical).'">'; }
        if ($this->prev)      { $out[] = '<link rel="prev" href="'.e($this->prev).'">'; }
        if ($this->next)      { $out[] = '<link rel="next" href="'.e($this->next).'">'; }

        // Open Graph
        $og = $this->buildOg();
        foreach ($og as $p => $v) {
            if ($v !== null && $v !== '') {
                $out[] = '<meta property="og:'.$p.'" content="'.e((string)$v).'">';
            }
        }

        // Twitter
        $tw = $this->buildTwitter();
        foreach ($tw as $p => $v) {
            if ($v !== null && $v !== '') {
                $out[] = '<meta name="twitter:'.$p.'" content="'.e((string)$v).'">';
            }
        }

        // Ikony / manifest
        $out = array_merge($out, $this->renderIcons());

        return implode("\n", $out);
    }

    private function buildOg(): array
    {
        $og = $this->og;
        $og['title']       = $og['title']       ?? $this->title;
        $og['site_name']   = $og['site_name']   ?? $this->siteName ?? config('app.name');
        $og['description'] = $og['description'] ?? $this->description;
        $og['url']         = $og['url']         ?? ($this->canonical ?? url()->current());
        $og['locale']      = $og['locale']      ?? str_replace('_', '-', app()->getLocale() ?? 'pl-PL');
        return $og;
    }

    private function buildTwitter(): array
    {
        $tw = $this->twitter;
        $tw['title']       = $tw['title']       ?? $this->title ?? $this->siteName ?? config('app.name');
        $tw['description'] = $tw['description'] ?? $this->description;
        $tw['image']       = $tw['image']       ?? ($this->og['image'] ?? null);
        return $tw;
    }

    private function renderIcons(): array
    {
        $out = [];

        $push = static function (array &$out, string $htmlKey, string $html): void {
            // proste odfiltrowanie duplikatów po identycznym HTML
            if (!in_array($html, $out, true)) {
                $out[] = $html;
            }
        };

        // 1) Pojedynczy favicon (kompatybilność wstecz)
        if (!empty($this->icons['favicon']) && is_string($this->icons['favicon'])) {
            $push($out, 'favicon', '<link rel="icon" href="'.e($this->icons['favicon']).'">');
        }

        // 2) Apple touch icons (tablica wariantów lub proste stringi)
        // Akceptowane formy:
        //  - 'apple_touch' => ['/path/a.png', '/path/b.png']  // bez sizes
        //  - 'apple_touch' => [['href'=>'/path/a.png','sizes'=>'57x57'], ...] // z sizes
        if (!empty($this->icons['apple_touch']) && is_array($this->icons['apple_touch'])) {
            foreach ($this->icons['apple_touch'] as $icon) {
                if (is_string($icon)) {
                    $push($out, 'apple', '<link rel="apple-touch-icon" href="'.e($icon).'">');
                } elseif (is_array($icon) && !empty($icon['href'])) {
                    $attrs = [
                        'rel="apple-touch-icon"',
                        'href="'.e($icon['href']).'"',
                    ];
                    if (!empty($icon['sizes'])) {
                        $attrs[] = 'sizes="'.e($icon['sizes']).'"';
                    }
                    $push($out, 'apple', '<link '.implode(' ', $attrs).'>');
                }
            }
        }

        // 3) Klasyczne <link rel="icon"> w wielu rozmiarach/typach
        // Akceptowane formy:
        //  - 'icon' => ['/path/32.png', '/path/16.png']
        //  - 'icon' => [['href'=>'/path/32.png','sizes'=>'32x32','type'=>'image/png'], ...]
        if (!empty($this->icons['icon']) && is_array($this->icons['icon'])) {
            foreach ($this->icons['icon'] as $icon) {
                if (is_string($icon)) {
                    $push($out, 'icon', '<link rel="icon" href="'.e($icon).'">');
                } elseif (is_array($icon) && !empty($icon['href'])) {
                    $attrs = [
                        'rel="icon"',
                        'href="'.e($icon['href']).'"',
                    ];
                    if (!empty($icon['type'])) {
                        $attrs[] = 'type="'.e($icon['type']).'"';
                    }
                    if (!empty($icon['sizes'])) {
                        $attrs[] = 'sizes="'.e($icon['sizes']).'"';
                    }
                    $push($out, 'icon', '<link '.implode(' ', $attrs).'>');
                }
            }
        }

        // 4) Manifest (jeśli podany jako string)
        if (!empty($this->icons['manifest']) && is_string($this->icons['manifest'])) {
            $push($out, 'manifest', '<link rel="manifest" href="'.e($this->icons['manifest']).'">');
        }

        // 5) Microsoft tiles (opcjonalnie; tylko jeśli ktoś wstawi w icons — nic nie dodajemy automatycznie)
        //  - 'ms_tile_image' => '...'
        //  - 'ms_tile_color' => '#fff'
        if (!empty($this->icons['ms_tile_color']) && is_string($this->icons['ms_tile_color'])) {
            $push($out, 'mscolor', '<meta name="msapplication-TileColor" content="'.e($this->icons['ms_tile_color']).'">');
        }
        if (!empty($this->icons['ms_tile_image']) && is_string($this->icons['ms_tile_image'])) {
            $push($out, 'mstile', '<meta name="msapplication-TileImage" content="'.e($this->icons['ms_tile_image']).'">');
        }

        // 6) Mask icon (Safari) – jeśli ktoś ustawi w icons; nic nie wymyślamy
        if (!empty($this->icons['mask_icon']) && is_string($this->icons['mask_icon'])) {
            $color = !empty($this->icons['mask_color']) ? $this->icons['mask_color'] : '#000000';
            $push($out, 'mask', '<link rel="mask-icon" href="'.e($this->icons['mask_icon']).'" color="'.e($color).'">');
        }

        return $out;
    }

}
