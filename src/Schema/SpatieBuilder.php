<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Schema;

use Spatie\SchemaOrg\Schema;

final class SpatieBuilder
{
    /**
     * Buduje węzeł za pomocą spatie/schema-org. Zwraca tablicę zdekodowaną z JSON.
     *
     * @param array<string,mixed> $d
     * @return array<string,mixed>|null
     */
    public static function build(string $type, array $d): ?array
    {
        if (!\class_exists(Schema::class)) {
            return null;
        }

        switch ($type) {
            case Types::PRODUCT:
                $node = Schema::product()
                    ->name($d['name'] ?? \config('app.name'))
                    ->image((array)($d['image'] ?? []))
                    ->description($d['description'] ?? null)
                    ->sku($d['sku'] ?? null);

                if (!empty($d['brand'])) {
                    $brand = \is_array($d['brand'])
                        ? Schema::brand()->name($d['brand']['name'] ?? null)
                        : Schema::brand()->name($d['brand']);
                    $node->brand($brand);
                }
                if (!empty($d['offers'])) {
                    $offers = [];
                    foreach ((array)$d['offers'] as $o) {
                        $offers[] = Schema::offer()
                            ->price($o['price'] ?? null)
                            ->priceCurrency($o['priceCurrency'] ?? ($o['currency'] ?? null))
                            ->availability($o['availability'] ?? null)
                            ->url($o['url'] ?? null);
                    }
                    $node->offers($offers);
                }
                return \json_decode($node->toScript(), true);

            case Types::ARTICLE:
            case Types::NEWSARTICLE:
            case Types::BLOGPOSTING:
                $builder = match ($type) {
                    Types::NEWSARTICLE => Schema::newsArticle(),
                    Types::BLOGPOSTING => Schema::blogPosting(),
                    default            => Schema::article(),
                };
                $node = $builder
                    ->headline($d['headline'] ?? ($d['title'] ?? null))
                    ->description($d['description'] ?? null)
                    ->image((array)($d['image'] ?? []))
                    ->datePublished($d['datePublished'] ?? null)
                    ->dateModified($d['dateModified'] ?? null)
                    ->mainEntityOfPage($d['url'] ?? ($d['mainEntityOfPage'] ?? null));

                if (!empty($d['author'])) {
                    $node->author(Schema::person()->name(\is_array($d['author']) ? ($d['author']['name'] ?? null) : $d['author']));
                }
                if (!empty($d['publisher'])) {
                    $node->publisher(Schema::organization()->name(\is_array($d['publisher']) ? ($d['publisher']['name'] ?? null) : $d['publisher']));
                }
                return \json_decode($node->toScript(), true);

            case Types::BREADCRUMB:
                $items = [];
                foreach ((array)($d['items'] ?? []) as $i => $item) {
                    $items[] = Schema::listItem()
                        ->position($item['position'] ?? ($i + 1))
                        ->name($item['name'] ?? null)
                        ->item($item['item'] ?? ($item['url'] ?? null));
                }
                $node = Schema::breadcrumbList()->itemListElement($items);
                return \json_decode($node->toScript(), true);

            case Types::LOCALBUSINESS:
                $node = Schema::localBusiness()
                    ->name($d['name'] ?? \config('app.name'))
                    ->image((array)($d['image'] ?? []))
                    ->url($d['url'] ?? null)
                    ->telephone($d['telephone'] ?? null);
                if (!empty($d['address']))      { $node->address($d['address']); }
                if (!empty($d['openingHours'])) { $node->openingHours($d['openingHours']); }
                if (!empty($d['sameAs']))       { $node->sameAs((array)$d['sameAs']); }
                return \json_decode($node->toScript(), true);

            case Types::EVENT:
                $node = Schema::event()
                    ->name($d['name'] ?? null)
                    ->startDate($d['startDate'] ?? null)
                    ->endDate($d['endDate'] ?? null)
                    ->image((array)($d['image'] ?? []))
                    ->description($d['description'] ?? null);
                if (!empty($d['location'])) { $node->location($d['location']); }
                if (!empty($d['offers'])) {
                    $offers = [];
                    foreach ((array)$d['offers'] as $o) {
                        $offers[] = Schema::offer()
                            ->price($o['price'] ?? null)
                            ->priceCurrency($o['priceCurrency'] ?? ($o['currency'] ?? null))
                            ->availability($o['availability'] ?? null)
                            ->url($o['url'] ?? null);
                    }
                    $node->offers($offers);
                }
                return \json_decode($node->toScript(), true);

            case Types::VIDEOOBJECT:
                $node = Schema::videoObject()
                    ->name($d['name'] ?? null)
                    ->description($d['description'] ?? null)
                    ->thumbnailUrl((array)($d['thumbnailUrl'] ?? []))
                    ->uploadDate($d['uploadDate'] ?? null)
                    ->duration($d['duration'] ?? null)
                    ->contentUrl($d['contentUrl'] ?? null)
                    ->embedUrl($d['embedUrl'] ?? null);
                return \json_decode($node->toScript(), true);

            case Types::RECIPE:
                $node = Schema::recipe()
                    ->name($d['name'] ?? null)
                    ->description($d['description'] ?? null)
                    ->image((array)($d['image'] ?? []))
                    ->totalTime($d['totalTime'] ?? null);
                if (!empty($d['recipeIngredient']))   { $node->recipeIngredient((array)$d['recipeIngredient']); }
                if (!empty($d['recipeInstructions'])) { $node->recipeInstructions((array)$d['recipeInstructions']); }
                return \json_decode($node->toScript(), true);
        }

        return null;
    }
}
