<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Seo\Images;

final class ResponsiveImageRenderer
{
    /** @var array<string,mixed> */
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param array<int,array<string,mixed>> $sources
     * @param array<string,mixed> $imgAttributes
     * @param array<string,mixed> $pictureAttributes
     */
    public function picture(array $sources, array $imgAttributes = [], array $pictureAttributes = []): string
    {
        $imgAttributes['loading'] = $imgAttributes['loading'] ?? ($this->config['default_loading'] ?? 'lazy');
        $imgAttributes['decoding'] = $imgAttributes['decoding'] ?? ($this->config['default_decoding'] ?? 'async');
        $imgAttributes['fetchpriority'] = $imgAttributes['fetchpriority'] ?? ($this->config['default_fetchpriority'] ?? 'auto');

        $html = ['<picture' . $this->attributes($pictureAttributes) . '>'];

        foreach ($sources as $source) {
            if (empty($source['srcset'])) {
                continue;
            }

            $sourceAttributes = [
                'type' => $source['type'] ?? null,
                'srcset' => $source['srcset'],
                'sizes' => $source['sizes'] ?? null,
                'media' => $source['media'] ?? null,
            ];

            $html[] = '<source' . $this->attributes($sourceAttributes) . '>';
        }

        $html[] = '<img' . $this->attributes($imgAttributes) . '>';
        $html[] = '</picture>';

        return implode('', $html);
    }

    /**
     * @param array<string,mixed> $formats
     * @param array<string,mixed> $imgAttributes
     * @param array<string,mixed> $pictureAttributes
     */
    public function pictureFromFormats(array $formats, array $imgAttributes = [], array $pictureAttributes = []): string
    {
        $sources = [];

        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $format => $mime) {
            if (!empty($formats[$format])) {
                $sources[] = [
                    'type' => $mime,
                    'srcset' => $formats[$format],
                ];
            }
        }

        $imgAttributes['src'] = $imgAttributes['src']
            ?? $formats['jpg']
            ?? $formats['jpeg']
            ?? $formats['png']
            ?? $formats['webp']
            ?? $formats['avif']
            ?? '';

        return $this->picture($sources, $imgAttributes, $pictureAttributes);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function attributes(array $attributes): string
    {
        $compiled = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if ($value === true) {
                $compiled[] = ' ' . e((string) $name);
                continue;
            }

            $compiled[] = ' ' . e((string) $name) . '="' . e((string) $value) . '"';
        }

        return implode('', $compiled);
    }
}
