<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Seo\Schema;

final class SchemaPresetFactory
{
    /** @var array<string,mixed> */
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function make(string $preset, array $data = []): array
    {
        $definition = $this->config[$preset] ?? ['type' => $preset, 'defaults' => []];
        $type = (string) ($definition['type'] ?? $preset);
        $payload = array_replace_recursive((array) ($definition['defaults'] ?? []), $data);

        return match ($type) {
            'organization' => [$this->organization($payload)],
            'website' => [$this->website($payload)],
            'web_page' => [$this->webPage($payload)],
            'home_page' => $this->homePage($payload),
            'service_page' => $this->servicePage($payload),
            'product_page' => $this->productPage($payload),
            'faq_page' => $this->faqPage($payload),
            default => [(array) $payload],
        };
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,array<string,mixed>>
     */
    private function homePage(array $data): array
    {
        return [
            $this->organization($data['organization'] ?? $data),
            $this->website($data['website'] ?? $data),
            $this->webPage($data['web_page'] ?? $data),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,array<string,mixed>>
     */
    private function servicePage(array $data): array
    {
        $schemas = [
            $this->organization($data['organization'] ?? $data),
            $this->webPage($data['web_page'] ?? $data),
            ['@type' => 'Service'] + ($data['service'] ?? $data),
        ];

        if (!empty($data['breadcrumbs'])) {
            $schemas[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => $data['breadcrumbs'],
            ];
        }

        if (!empty($data['faq'])) {
            $schemas[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $data['faq'],
            ];
        }

        return $schemas;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,array<string,mixed>>
     */
    private function productPage(array $data): array
    {
        $schemas = [
            $this->organization($data['organization'] ?? $data),
            $this->webPage($data['web_page'] ?? $data),
            ['@type' => 'Product'] + ($data['product'] ?? $data),
        ];

        if (!empty($data['breadcrumbs'])) {
            $schemas[] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => $data['breadcrumbs'],
            ];
        }

        return $schemas;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,array<string,mixed>>
     */
    private function faqPage(array $data): array
    {
        $schemas = [
            $this->webPage($data['web_page'] ?? $data),
            [
                '@type' => 'FAQPage',
                'mainEntity' => $data['faq'] ?? [],
            ],
        ];

        if (!empty($data['organization'])) {
            $schemas[] = $this->organization((array) $data['organization']);
        }

        return $schemas;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function organization(array $data): array
    {
        return [
            '@type' => $data['@type'] ?? 'Organization',
            'name' => $data['name'] ?? config('app.name'),
            'url' => $data['url'] ?? url('/'),
            'logo' => $data['logo'] ?? null,
            'sameAs' => $data['sameAs'] ?? null,
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function website(array $data): array
    {
        return [
            '@type' => 'WebSite',
            'name' => $data['name'] ?? config('app.name'),
            'url' => $data['url'] ?? url('/'),
            'inLanguage' => $data['inLanguage'] ?? str_replace('_', '-', app()->getLocale() ?? 'pl-PL'),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function webPage(array $data): array
    {
        return [
            '@type' => 'WebPage',
            'name' => $data['name'] ?? config('app.name'),
            'url' => $data['url'] ?? url()->current(),
            'description' => $data['description'] ?? null,
            'isPartOf' => $data['isPartOf'] ?? [
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
        ];
    }
}
