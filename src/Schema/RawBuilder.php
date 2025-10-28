<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Schema;

final class RawBuilder
{
    /** @var array<int,string> */
    private static array $errors = [];

    /**
     * @internal używane przez ThemeStructuredData
     * @return array<int,string>
     */
    public static function pullErrors(): array
    {
        $e = self::$errors;
        self::$errors = [];
        return $e;
    }

    /**
     * @param array<string,mixed> $d
     * @return array<string,mixed>
     */
    public static function build(string $type, array $d, string $onInvalid, bool $attachLang): array
    {
        $node = ['@type' => $type];

        $add = static function (string $prop, mixed $value) use (&$node): void {
            if ($value === null) { return; }
            if (\is_string($value) && \trim($value) === '') { return; }
            if (\is_array($value) && empty($value)) { return; }
            $node[$prop] = $value;
        };
        $asArray = static fn(mixed $v): array => $v === null ? [] : (\is_array($v) ? $v : [$v]);
        $asImages = static function (mixed $v) use ($asArray): array {
            $arr = $asArray($v);
            return \array_values(\array_filter($arr, static fn($x) => \is_string($x) ? \trim($x) !== '' : !empty($x)));
        };
        $asPersonOrOrg = static function (mixed $v, bool $preferOrg = false): ?array {
            if (\is_array($v)) {
                if (isset($v['@type'])) { return $v; }
                if (isset($v['name']))  { return ['@type' => ($preferOrg ? 'Organization' : 'Person')] + $v; }
                return null;
            }
            if (\is_string($v)) {
                return ['@type' => ($preferOrg ? 'Organization' : 'Person'), 'name' => $v];
            }
            return null;
        };

        $normalizeOffers = static function (mixed $offers) use ($onInvalid): array {
            $allowedAvailability = [
                'https://schema.org/InStock',
                'https://schema.org/OutOfStock',
                'https://schema.org/PreOrder',
                'https://schema.org/PreSale',
                'https://schema.org/SoldOut',
                'https://schema.org/LimitedAvailability',
                'https://schema.org/OnlineOnly',
                'https://schema.org/InStoreOnly',
                'https://schema.org/Discontinued',
            ];
            $allowedItemCondition = [
                'https://schema.org/NewCondition',
                'https://schema.org/UsedCondition',
                'https://schema.org/RefurbishedCondition',
                'https://schema.org/DamagedCondition',
            ];

            $out = [];
            foreach ((array)$offers as $o) {
                if (!\is_array($o)) { continue; }
                $entry = \array_filter([
                    '@type'          => 'Offer',
                    'price'          => $o['price'] ?? null,
                    'priceCurrency'  => $o['priceCurrency'] ?? ($o['currency'] ?? null),
                    'availability'   => $o['availability'] ?? null,
                    'url'            => $o['url'] ?? null,
                    'priceValidUntil'=> $o['priceValidUntil'] ?? null,
                    'itemCondition'  => $o['itemCondition'] ?? null,
                    'seller'         => $o['seller'] ?? null,
                ], static fn($v) => $v !== null);

                if (empty($entry['price']) || empty($entry['priceCurrency'])) {
                    $msg = 'Offer missing price/priceCurrency';
                    if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                    self::$errors[] = $msg;
                    continue;
                }
                if (!empty($entry['url']) && !FormatValidator::isUrl($entry['url'])) {
                    $msg = 'Offer.url must be a valid URL';
                    if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                    self::$errors[] = $msg;
                    unset($entry['url']);
                }
                if (!empty($entry['priceValidUntil']) && !FormatValidator::isIso8601Date($entry['priceValidUntil'])) {
                    $msg = 'Offer.priceValidUntil must be ISO8601 date/datetime';
                    if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                    self::$errors[] = $msg;
                    unset($entry['priceValidUntil']);
                }
                if (!empty($entry['availability'])
                    && !\in_array($entry['availability'], $allowedAvailability, true)
                    && !FormatValidator::isUrl($entry['availability'])) {
                    $msg = 'Offer.availability must be schema.org URL enum or valid URL';
                    if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                    self::$errors[] = $msg;
                    unset($entry['availability']);
                }
                if (!empty($entry['itemCondition'])
                    && !\in_array($entry['itemCondition'], $allowedItemCondition, true)
                    && !FormatValidator::isUrl($entry['itemCondition'])) {
                    $msg = 'Offer.itemCondition must be schema.org URL enum or valid URL';
                    if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                    self::$errors[] = $msg;
                    unset($entry['itemCondition']);
                }
                $out[] = $entry;
            }
            return $out;
        };

        $normalizeAggregateRating = static function (mixed $r) use ($onInvalid): ?array {
            if (!\is_array($r)) { return null; }
            $entry = \array_filter([
                '@type'        => 'AggregateRating',
                'ratingValue'  => $r['ratingValue'] ?? null,
                'reviewCount'  => $r['reviewCount'] ?? ($r['ratingCount'] ?? null),
                'bestRating'   => $r['bestRating'] ?? null,
                'worstRating'  => $r['worstRating'] ?? null,
            ], static fn($v) => $v !== null);
            if (empty($entry['ratingValue'])) {
                $msg = 'AggregateRating missing ratingValue';
                if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                self::$errors[] = $msg;
                return null;
            }
            return $entry;
        };

        switch ($type) {
            case Types::PRODUCT:
                $add('name', $d['name'] ?? \config('app.name'));
                $add('description', $d['description'] ?? null);
                $add('image', $asImages($d['image'] ?? null));
                $add('sku', $d['sku'] ?? null);
                if (!empty($d['brand'])) {
                    $brand = \is_array($d['brand']) ? \array_merge(['@type' => 'Brand'], $d['brand']) : ['@type' => 'Brand', 'name' => $d['brand']];
                    $add('brand', $brand);
                }
                if (!empty($d['offers']))          { $add('offers', $normalizeOffers($d['offers'])); }
                if (!empty($d['aggregateRating'])) { $add('aggregateRating', $normalizeAggregateRating($d['aggregateRating'])); }
                break;

            case Types::ARTICLE:
            case Types::NEWSARTICLE:
            case Types::BLOGPOSTING:
                $add('headline', $d['headline'] ?? ($d['title'] ?? null));
                $add('description', $d['description'] ?? null);
                $add('image', $asImages($d['image'] ?? null));
                $add('datePublished', $d['datePublished'] ?? null);
                $add('dateModified', $d['dateModified'] ?? null);
                if (!empty($d['author']))    { $add('author', $asPersonOrOrg($d['author'])); }
                if (!empty($d['publisher'])) { $add('publisher', $asPersonOrOrg($d['publisher'], true)); }
                $add('mainEntityOfPage', $d['url'] ?? ($d['mainEntityOfPage'] ?? null));
                break;

            case Types::BREADCRUMB:
                $items = [];
                foreach ((array)($d['items'] ?? []) as $i => $it) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $it['position'] ?? ($i + 1),
                        'name'     => $it['name'] ?? null,
                        'item'     => $it['item'] ?? ($it['url'] ?? null),
                    ];
                }
                $add('itemListElement', $items);
                break;

            case Types::EVENT:
                $add('name', $d['name'] ?? null);
                $add('startDate', $d['startDate'] ?? null);
                $add('endDate', $d['endDate'] ?? null);
                $add('eventStatus', $d['eventStatus'] ?? null);
                $add('eventAttendanceMode', $d['eventAttendanceMode'] ?? null);
                $add('location', $d['location'] ?? null);
                $add('image', $asImages($d['image'] ?? null));
                $add('description', $d['description'] ?? null);
                if (!empty($d['offers']))    { $add('offers', $normalizeOffers($d['offers'])); }
                if (!empty($d['organizer'])) { $add('organizer', $asPersonOrOrg($d['organizer'], true)); }
                if (!empty($d['performer'])) { $add('performer', $asPersonOrOrg($d['performer'])); }
                break;

            case Types::FAQPAGE:
                $faq = [];
                foreach ((array)($d['faqs'] ?? []) as $f) {
                    $faq[] = [
                        '@type' => 'Question',
                        'name'  => $f['question'] ?? null,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => $f['answer'] ?? null,
                        ],
                    ];
                }
                $add('mainEntity', $faq);
                break;

            case Types::HOWTO:
                $add('name', $d['name'] ?? null);
                $add('description', $d['description'] ?? null);
                $add('image', $asImages($d['image'] ?? null));
                $add('totalTime', $d['totalTime'] ?? null);
                $add('tool', $d['tool'] ?? null);
                $add('supply', $d['supply'] ?? null);
                $add('step', $d['step'] ?? null);
                break;

            case Types::LOCALBUSINESS:
                $add('name', $d['name'] ?? \config('app.name'));
                $add('image', $asImages($d['image'] ?? null));
                $add('url', $d['url'] ?? null);
                $add('telephone', $d['telephone'] ?? null);
                $add('address', $d['address'] ?? null);
                $add('geo', $d['geo'] ?? null);
                $add('openingHours', $d['openingHours'] ?? null);
                $add('sameAs', $d['sameAs'] ?? null);
                break;

            case Types::VIDEOOBJECT:
                $add('name', $d['name'] ?? null);
                $add('description', $d['description'] ?? null);
                $add('thumbnailUrl', $asArray($d['thumbnailUrl'] ?? null));
                $add('uploadDate', $d['uploadDate'] ?? null);
                $add('duration', $d['duration'] ?? null);
                $add('contentUrl', $d['contentUrl'] ?? null);
                $add('embedUrl', $d['embedUrl'] ?? null);
                if (!empty($d['publisher'])) { $add('publisher', $asPersonOrOrg($d['publisher'], true)); }
                break;

            case Types::RECIPE:
                $add('name', $d['name'] ?? null);
                $add('description', $d['description'] ?? null);
                $add('image', $asImages($d['image'] ?? null));
                $add('recipeIngredient', $asArray($d['recipeIngredient'] ?? null));
                $add('recipeInstructions', $asArray($d['recipeInstructions'] ?? null));
                if (!empty($d['aggregateRating'])) { $add('aggregateRating', $normalizeAggregateRating($d['aggregateRating'])); }
                if (!empty($d['author']))          { $add('author', $asPersonOrOrg($d['author'])); }
                $add('totalTime', $d['totalTime'] ?? null);
                break;

            case Types::SOFTWAREAPPLICATION:
                $add('name', $d['name'] ?? null);
                $add('operatingSystem', $d['operatingSystem'] ?? null);
                $add('applicationCategory', $d['applicationCategory'] ?? null);
                if (!empty($d['offers']))          { $add('offers', $normalizeOffers($d['offers'])); }
                if (!empty($d['aggregateRating'])) { $add('aggregateRating', $normalizeAggregateRating($d['aggregateRating'])); }
                break;

            case Types::JOBPOSTING:
                $add('title', $d['title'] ?? null);
                $add('description', $d['description'] ?? null);
                $add('datePosted', $d['datePosted'] ?? null);
                $add('validThrough', $d['validThrough'] ?? null);
                $add('employmentType', $d['employmentType'] ?? null);
                if (!empty($d['hiringOrganization'])) { $add('hiringOrganization', $asPersonOrOrg($d['hiringOrganization'], true)); }
                $add('jobLocation', $d['jobLocation'] ?? null);
                $add('baseSalary', $d['baseSalary'] ?? null);
                break;

            case Types::ITEMLIST:
                $items = [];
                foreach ((array)($d['items'] ?? []) as $i => $it) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $it['position'] ?? ($i + 1),
                        'url'      => $it['url'] ?? ($it['item'] ?? null),
                        'name'     => $it['name'] ?? null,
                    ];
                }
                $add('itemListElement', $items);
                break;

            case Types::IMAGEOBJECT:
                $add('contentUrl', $d['contentUrl'] ?? ($d['url'] ?? null));
                $add('caption', $d['caption'] ?? null);
                $add('width', $d['width'] ?? null);
                $add('height', $d['height'] ?? null);
                break;

            case Types::WEBSITE:
                $add('url', $d['url'] ?? null);
                $add('name', $d['name'] ?? \config('app.name'));
                if (!empty($d['searchUrl'])) {
                    $add('potentialAction', [[
                        '@type'       => 'SearchAction',
                        'target'      => $d['searchUrl'],
                        'query-input' => 'required name=query',
                    ]]);
                }
                break;

            case Types::WEBPAGE:
                $add('url', $d['url'] ?? null);
                $add('name', $d['name'] ?? ($d['title'] ?? null));
                $add('isPartOf', $d['isPartOf'] ?? null);
                $add('breadcrumb', $d['breadcrumb'] ?? null);
                $add('primaryImageOfPage', $d['primaryImageOfPage'] ?? null);
                break;

            case Types::ORGANIZATION:
            default:
                $add('name', $d['name'] ?? \config('app.name'));
                $add('url', $d['url'] ?? null);
                $add('logo', $d['logo'] ?? null);
                $add('sameAs', $d['sameAs'] ?? null);
                $add('contactPoint', $d['contactPoint'] ?? null);
                break;
        }

        if ($attachLang && !isset($node['inLanguage'])) {
            $node['inLanguage'] = \str_replace('_', '-', app()->getLocale() ?? 'pl-PL');
        }

        // Minimal required by Google Rich Results / common shapes
        self::validateRequired($type, $node, $onInvalid);
        // Format checks
        self::validateFormats($type, $node, $onInvalid);

        return $node;
    }

    /** @param array<string,mixed> $node */
    private static function validateFormats(string $type, array &$node, string $onInvalid): void
    {
        $ensureUrl = static function (string $prop) use (&$node, $onInvalid): void {
            if (!isset($node[$prop])) { return; }
            if (!FormatValidator::isUrl($node[$prop])) {
                $msg = $prop . ' must be a valid URL';
                if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                self::$errors[] = $msg;
                unset($node[$prop]);
            }
        };

        foreach (['url','contentUrl','embedUrl','logo'] as $p) { $ensureUrl($p); }

        foreach (['datePublished','dateModified','startDate','endDate','uploadDate','priceValidUntil'] as $p) {
            if (isset($node[$p]) && !FormatValidator::isIso8601Date($node[$p])) {
                $msg = $p . ' must be ISO8601 date/datetime';
                if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                self::$errors[] = $msg;
                unset($node[$p]);
            }
        }

        if ($type === Types::VIDEOOBJECT && isset($node['duration']) && !FormatValidator::isIso8601Duration($node['duration'])) {
            $msg = 'duration must be ISO8601 duration';
            if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
            self::$errors[] = $msg;
            unset($node['duration']);
        }
    }

    /** @param array<string,mixed> $node */
    private static function validateRequired(string $type, array $node, string $onInvalid): void
    {
        $required = [
            Types::PRODUCT             => ['name','offers'],
            Types::ARTICLE             => ['headline'],
            Types::NEWSARTICLE         => ['headline'],
            Types::BLOGPOSTING         => ['headline'],
            Types::BREADCRUMB          => ['itemListElement'],
            Types::EVENT               => ['name','startDate','location'],
            Types::FAQPAGE             => ['mainEntity'],
            Types::HOWTO               => ['name','step'],
            Types::LOCALBUSINESS       => ['name','address'],
            Types::VIDEOOBJECT         => ['name','thumbnailUrl','uploadDate'],
            Types::RECIPE              => ['name','recipeIngredient','recipeInstructions'],
            Types::SOFTWAREAPPLICATION => ['name','operatingSystem'],
            Types::JOBPOSTING          => ['title','hiringOrganization','jobLocation'],
            Types::ITEMLIST            => ['itemListElement'],
            Types::IMAGEOBJECT         => ['contentUrl'],
            Types::WEBSITE             => ['url','name'],
            Types::WEBPAGE             => ['url','name'],
            Types::ORGANIZATION        => ['name'],
        ];

        if (!isset($required[$type])) { return; }
        foreach ($required[$type] as $prop) {
            $present = \array_key_exists($prop, $node)
                && (!\is_array($node[$prop]) ? ($node[$prop] !== null && $node[$prop] !== '') : !empty($node[$prop]));
            if (!$present) {
                $msg = \sprintf('%s missing required property: %s', $type, $prop);
                if ($onInvalid === 'error') { throw new \InvalidArgumentException($msg); }
                self::$errors[] = $msg;
            }
        }
    }
}
