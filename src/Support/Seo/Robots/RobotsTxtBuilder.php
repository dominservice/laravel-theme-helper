<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Seo\Robots;

final class RobotsTxtBuilder
{
    /** @var array<string,mixed> */
    private array $config;

    /** @var array<int,array<string,mixed>> */
    private array $groups = [];

    /** @var array<int,string> */
    private array $sitemaps = [];

    private ?string $host = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->groups = array_values($config['default_rules'] ?? []);
        $this->sitemaps = array_values($config['sitemaps'] ?? []);
        $this->host = $config['host'] ?? null;
    }

    public function addGroup(string $userAgent, array $allow = [], array $disallow = [], ?int $crawlDelay = null): self
    {
        $this->groups[] = [
            'user_agent' => $userAgent,
            'allow' => array_values($allow),
            'disallow' => array_values($disallow),
            'crawl_delay' => $crawlDelay,
        ];

        return $this;
    }

    public function addSitemap(string $url): self
    {
        $this->sitemaps[] = $url;

        return $this;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function render(): string
    {
        $lines = [];

        foreach ($this->groups as $index => $group) {
            if ($index > 0) {
                $lines[] = '';
            }

            $lines[] = 'User-agent: ' . ($group['user_agent'] ?? ($this->config['default_user_agent'] ?? '*'));

            foreach ((array) ($group['allow'] ?? []) as $path) {
                $lines[] = 'Allow: ' . $path;
            }

            foreach ((array) ($group['disallow'] ?? []) as $path) {
                $lines[] = 'Disallow: ' . $path;
            }

            if (!empty($group['crawl_delay'])) {
                $lines[] = 'Crawl-delay: ' . (int) $group['crawl_delay'];
            }
        }

        if ($this->host) {
            $lines[] = '';
            $lines[] = 'Host: ' . $this->host;
        }

        foreach (array_unique($this->sitemaps) as $sitemap) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $sitemap;
        }

        return trim(implode("\n", $lines)) . "\n";
    }
}
