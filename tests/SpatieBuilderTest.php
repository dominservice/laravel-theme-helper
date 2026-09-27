<?php

declare(strict_types=1);

use Dominservice\LaravelThemeHelper\Schema\SpatieBuilder;
use Dominservice\LaravelThemeHelper\Schema\Types;
use PHPUnit\Framework\TestCase;

final class SpatieBuilderTest extends TestCase
{
    public function test_it_converts_the_current_spatie_schema_builder_to_an_array(): void
    {
        $schema = SpatieBuilder::build(Types::PRODUCT, [
            'name' => 'DominPress',
            'offers' => [
                [
                    'price' => '100.00',
                    'priceCurrency' => 'PLN',
                ],
            ],
        ]);

        self::assertIsArray($schema);
        self::assertSame('Product', $schema['@type']);
        self::assertSame('DominPress', $schema['name']);
        self::assertSame('PLN', $schema['offers'][0]['priceCurrency']);
    }
}
