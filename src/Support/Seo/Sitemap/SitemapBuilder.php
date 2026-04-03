<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Seo\Sitemap;

use DateTimeInterface;

final class SitemapBuilder
{
    /** @var array<string,mixed> */
    private array $config;

    /** @var array<int,array<string,mixed>> */
    private array $urls = [];

    /** @var array<int,array<string,mixed>> */
    private array $sitemaps = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function addUrl(string $loc, array $options = []): self
    {
        $entry = [
            'loc' => $loc,
            'lastmod' => $options['lastmod'] ?? null,
            'changefreq' => $options['changefreq'] ?? ($this->config['default_changefreq'] ?? null),
            'priority' => $options['priority'] ?? ($this->config['default_priority'] ?? null),
            'alternates' => array_values($options['alternates'] ?? []),
            'images' => array_values($options['images'] ?? []),
        ];

        $this->urls[] = $entry;

        return $this;
    }

    /**
     * @param array<int,array<string,mixed>> $entries
     */
    public function addUrls(array $entries): self
    {
        foreach ($entries as $entry) {
            $this->addUrl((string) ($entry['loc'] ?? ''), $entry);
        }

        return $this;
    }

    public function addSitemap(string $loc, ?DateTimeInterface $lastmod = null): self
    {
        $this->sitemaps[] = [
            'loc' => $loc,
            'lastmod' => $lastmod?->format(DATE_ATOM),
        ];

        return $this;
    }

    public function renderUrlset(): string
    {
        $xml = new \SimpleXMLElement('<urlset/>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        if ($this->hasAlternates()) {
            $xml->addAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }

        if ($this->hasImages()) {
            $xml->addAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        }

        foreach ($this->urls as $entry) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $this->xml($entry['loc']));

            if (!empty($this->config['include_lastmod']) && !empty($entry['lastmod'])) {
                $url->addChild('lastmod', $this->formatDate($entry['lastmod']));
            }

            if (!empty($entry['changefreq'])) {
                $url->addChild('changefreq', (string) $entry['changefreq']);
            }

            if ($entry['priority'] !== null && $entry['priority'] !== '') {
                $url->addChild('priority', (string) $entry['priority']);
            }

            foreach ($entry['alternates'] as $alternate) {
                $node = $url->addChild('xhtml:link', null, 'http://www.w3.org/1999/xhtml');
                $node->addAttribute('rel', 'alternate');
                $node->addAttribute('hreflang', (string) ($alternate['hreflang'] ?? ''));
                $node->addAttribute('href', (string) ($alternate['href'] ?? ''));
            }

            if (!empty($this->config['include_images'])) {
                foreach ($entry['images'] as $image) {
                    if (empty($image['loc'])) {
                        continue;
                    }

                    $imageNode = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                    $imageNode->addChild('image:loc', $this->xml((string) $image['loc']), 'http://www.google.com/schemas/sitemap-image/1.1');

                    if (!empty($image['title'])) {
                        $imageNode->addChild('image:title', $this->xml((string) $image['title']), 'http://www.google.com/schemas/sitemap-image/1.1');
                    }

                    if (!empty($image['caption'])) {
                        $imageNode->addChild('image:caption', $this->xml((string) $image['caption']), 'http://www.google.com/schemas/sitemap-image/1.1');
                    }
                }
            }
        }

        return (string) $xml->asXML();
    }

    public function renderIndex(): string
    {
        $xml = new \SimpleXMLElement('<sitemapindex/>');
        $xml->addAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($this->sitemaps as $entry) {
            $sitemap = $xml->addChild('sitemap');
            $sitemap->addChild('loc', $this->xml((string) $entry['loc']));

            if (!empty($entry['lastmod'])) {
                $sitemap->addChild('lastmod', $this->formatDate((string) $entry['lastmod']));
            }
        }

        return (string) $xml->asXML();
    }

    private function hasImages(): bool
    {
        foreach ($this->urls as $entry) {
            if (!empty($entry['images'])) {
                return true;
            }
        }

        return false;
    }

    private function hasAlternates(): bool
    {
        foreach ($this->urls as $entry) {
            if (!empty($entry['alternates'])) {
                return true;
            }
        }

        return false;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function formatDate(string|DateTimeInterface $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        return date(DATE_ATOM, strtotime($value));
    }
}
