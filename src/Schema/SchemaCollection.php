<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Schema;

final class SchemaCollection
{
    /** @var array<int,array<string,mixed>> */
    private array $schemas = [];

    /** @param array<string,mixed> $schema */
    public function add(array $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /** @param array<string,mixed> $data */
    public function addType(string $type, array $data = []): self
    {
        return $this->add(array_merge(['@type' => $type], $data));
    }

    /** @param array<string,mixed> $data */
    public function organization(array $data = []): self
    {
        return $this->addType(Types::ORGANIZATION, $data);
    }

    /** @param array<string,mixed> $data */
    public function localBusiness(array $data = []): self
    {
        return $this->addType(Types::LOCALBUSINESS, $data);
    }

    /** @param array<string,mixed> $data */
    public function product(array $data = []): self
    {
        return $this->addType(Types::PRODUCT, $data);
    }

    /** @param array<string,mixed> $data */
    public function service(array $data = []): self
    {
        return $this->addType(Types::SERVICE, $data);
    }

    /** @param array<string,mixed> $data */
    public function webPage(array $data = []): self
    {
        return $this->addType(Types::WEBPAGE, $data);
    }

    /** @param array<string,mixed> $data */
    public function webSite(array $data = []): self
    {
        return $this->addType(Types::WEBSITE, $data);
    }

    /** @param array<string,mixed> $data */
    public function faqPage(array $data = []): self
    {
        return $this->addType(Types::FAQPAGE, $data);
    }

    /** @param array<string,mixed> $data */
    public function breadcrumb(array $data = []): self
    {
        return $this->addType(Types::BREADCRUMB, $data);
    }

    /** @return array<int,array<string,mixed>> */
    public function toArray(): array
    {
        return $this->schemas;
    }

    public function isEmpty(): bool
    {
        return $this->schemas === [];
    }
}
