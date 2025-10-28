<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper;

use Dominservice\LaravelThemeHelper\Contracts\StructuredDataGenerator;
use Dominservice\LaravelThemeHelper\Schema\RawBuilder;
use Dominservice\LaravelThemeHelper\Schema\SpatieBuilder;
use Dominservice\LaravelThemeHelper\Schema\Types;

final class ThemeStructuredData implements StructuredDataGenerator
{
    /** @var array<int,string> */
    protected static array $schemaErrors = [];

    /** {@inheritdoc} */
    public static function pullSchemaErrors(): array
    {
        $e = self::$schemaErrors;
        self::$schemaErrors = [];
        return $e;
    }

    /** {@inheritdoc} */
    public static function smartStructuredData(array $data, array $options = []): string
    {
        $useSpatie  = \array_key_exists('use_spatie', $options) ? (bool)$options['use_spatie'] : true;
        $onInvalid  = $options['on_invalid'] ?? 'skip';
        $attachLang = \array_key_exists('attach_inLanguage', $options) ? (bool)$options['attach_inLanguage'] : true;

        // 1) RAW @graph – pełna dowolność
        if (!empty($data['schemas']) && \is_array($data['schemas'])) {
            $graph = \array_values($data['schemas']);
            foreach ($graph as &$node) {
                if ($attachLang && \is_array($node) && !isset($node['inLanguage'])) {
                    $node['inLanguage'] = \str_replace('_', '-', app()->getLocale() ?? 'pl-PL');
                }
                if (\is_array($node) && isset($node['@type'])) {
                    // lekkie format-checki; brak twardej walidacji wymaganych własności
                    RawBuilder::build((string)$node['@type'], $node, $onInvalid, $attachLang); // wykorzystuje walidatory
                    self::$schemaErrors = \array_merge(self::$schemaErrors, RawBuilder::pullErrors());
                }
            }
            unset($node);
            $payload = [
                '@context' => 'https://schema.org',
                '@graph'   => $graph,
            ];
            return self::toScript($payload);
        }

        // 2) Skróty
        $type = $data['type'] ?? Types::ORGANIZATION;

        // 2a) Spatie (jeśli wspierany typ)
        if ($useSpatie && \class_exists(\Spatie\SchemaOrg\Schema::class)) {
            try {
                $built = SpatieBuilder::build($type, $data);
                if ($built !== null) {
                    // format-check
                    RawBuilder::build($type, $built, $onInvalid, $attachLang); // tylko walidacja
                    self::$schemaErrors = \array_merge(self::$schemaErrors, RawBuilder::pullErrors());

                    $payload = [
                        '@context' => 'https://schema.org',
                        '@graph'   => [$built],
                    ];
                    return self::toScript($payload);
                }
            } catch (\Throwable $e) {
                self::$schemaErrors[] = 'Spatie fallback: ' . $e->getMessage();
            }
        }

        // 2b) RAW fallback
        $node = RawBuilder::build($type, $data, $onInvalid, $attachLang);
        self::$schemaErrors = \array_merge(self::$schemaErrors, RawBuilder::pullErrors());

        $payload = [
            '@context' => 'https://schema.org',
            '@graph'   => [$node],
        ];
        return self::toScript($payload);
    }

    /** @param array<string,mixed> $payload */
    private static function toScript(array $payload): string
    {
        return '<script type="application/ld+json">' .
            \json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT) .
            '</script>';
    }
}
