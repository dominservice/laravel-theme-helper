<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Theme.v3.php';

final class ThemeHelperStructuredDataTest extends TestCase
{
    /** Utility: decode JSON-LD payload */
    private function decodeGraph(string $html): array
    {
        $this->assertStringContainsString('<script type="application/ld+json">', $html);
        $json = trim(str_replace(['<script type="application/ld+json">','</script>'], '', $html));
        $payload = json_decode($json, true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('@context', $payload);
        $this->assertEquals('https://schema.org', $payload['@context']);
        $this->assertArrayHasKey('@graph', $payload);
        $this->assertIsArray($payload['@graph']);
        $this->assertNotEmpty($payload['@graph']);
        return $payload['@graph'];
    }

    private function assertNodeType(array $node, string $type): void
    {
        $this->assertArrayHasKey('@type', $node);
        $this->assertEquals($type, $node['@type']);
    }

    public function testProduct(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_PRODUCT,
            'name' => 'EcoChatka 70',
            'image' => ['https://example.com/i.jpg'],
            'offers' => [['price'=>'129900','priceCurrency'=>'PLN','availability'=>'https://schema.org/InStock','url'=>'https://example.com/p']],
            'aggregateRating' => ['ratingValue'=>4.8,'reviewCount'=>37],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'Product');
        $this->assertArrayHasKey('offers', $graph[0]);
    }

    public function testArticle(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_ARTICLE,
            'headline' => 'Tytuł',
            'image' => ['https://example.com/a.jpg'],
            'datePublished' => '2025-01-01T10:00:00+01:00',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'Article');
    }

    public function testBreadcrumb(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_BREADCRUMB,
            'items' => [
                ['name'=>'Start','item'=>'https://example.com/'],
                ['name'=>'Kategoria','item'=>'https://example.com/kategoria'],
            ]
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'BreadcrumbList');
        $this->assertArrayHasKey('itemListElement', $graph[0]);
    }

    public function testEvent(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_EVENT,
            'name' => 'Targi',
            'startDate' => '2025-05-01T12:00:00+01:00',
            'endDate' => '2025-05-01T18:00:00+01:00',
            'location' => ['@type'=>'Place','name'=>'Hala','address'=>'Miasto'],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'Event');
    }

    public function testFAQ(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_FAQPAGE,
            'faqs' => [['question'=>'Q?','answer'=>'A.']],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'FAQPage');
    }

    public function testHowTo(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_HOWTO,
            'name' => 'Instrukcja',
            'step' => ['Krok 1','Krok 2'],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'HowTo');
    }

    public function testLocalBusiness(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_LOCALBUSINESS,
            'name' => 'Dominwise',
            'address' => 'Address, City',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'LocalBusiness');
    }

    public function testVideoObject(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_VIDEOOBJECT,
            'name' => 'Film',
            'thumbnailUrl' => ['https://example.com/t.jpg'],
            'uploadDate' => '2025-01-02',
            'duration' => 'PT1H2M3S',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'VideoObject');
    }

    public function testRecipe(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_RECIPE,
            'name' => 'Przepis',
            'recipeIngredient' => ['1kg mąki'],
            'recipeInstructions' => ['Wymieszaj', 'Upiecz'],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'Recipe');
    }

    public function testSoftwareApplication(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_SOFTWAREAPPLICATION,
            'name' => 'App',
            'operatingSystem' => 'Linux',
            'applicationCategory' => 'DeveloperApplication',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'SoftwareApplication');
    }

    public function testJobPosting(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_JOBPOSTING,
            'title' => 'PHP Dev',
            'hiringOrganization' => 'Dominwise',
            'jobLocation' => ['@type'=>'Place','address'=>'PL'],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'JobPosting');
    }

    public function testItemList(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_ITEMLIST,
            'items' => [
                ['name'=>'A','url'=>'https://example.com/a'],
                ['name'=>'B','url'=>'https://example.com/b'],
            ],
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'ItemList');
    }

    public function testImageObject(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_IMAGEOBJECT,
            'contentUrl' => 'https://example.com/i.jpg',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'ImageObject');
    }

    public function testWebSite(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_WEBSITE,
            'url' => 'https://example.com/',
            'name' => 'Example',
            'searchUrl' => 'https://example.com/search?q={query}',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'WebSite');
    }

    public function testWebPage(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_WEBPAGE,
            'url' => 'https://example.com/page',
            'name' => 'Page',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'WebPage');
    }

    public function testOrganization(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_ORGANIZATION,
            'name' => 'Dominwise',
        ], ['on_invalid'=>'error','use_spatie'=>false]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'Organization');
    }

    public function testSchemasPassThrough(): void
    {
        $html = ThemeHelper::smartStructuredData([
            'schemas' => [
                ['@type'=>'Organization','name'=>'Dominwise','url'=>'https://example.com'],
                ['@type'=>'WebSite','url'=>'https://example.com','name'=>'Example'],
            ]
        ], ['on_invalid'=>'error']);
        $graph = $this->decodeGraph($html);
        $this->assertGreaterThanOrEqual(2, count($graph));
    }

    public function testSpatieIfAvailable(): void
    {
        if (!class_exists(\Spatie\SchemaOrg\Schema::class)) {
            $this->markTestSkipped('Spatie not installed');
        }
        $html = ThemeHelper::smartStructuredData([
            'type' => ThemeHelper::SCHEMA_PRODUCT,
            'name' => 'EcoChatka 70',
            'offers' => [['price'=>'129900','priceCurrency'=>'PLN']],
        ], ['on_invalid'=>'error','use_spatie'=>true]);
        $graph = $this->decodeGraph($html);
        $this->assertNodeType($graph[0], 'Product');
    }
}
