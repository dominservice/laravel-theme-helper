<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Themes;

use InvalidArgumentException;

final class ThemeRegistry
{
    /** @var array<string,ThemeDefinition> */
    private array $themes = [];

    /** @param array<string,array<string,mixed>> $themes */
    public function __construct(array $themes = [])
    {
        foreach ($themes as $key => $config) {
            $this->register($key, $config);
        }
    }

    /** @param array<string,mixed>|ThemeDefinition $definition */
    public function register(string $key, array|ThemeDefinition $definition): self
    {
        $this->themes[$key] = $definition instanceof ThemeDefinition
            ? $definition
            : ThemeDefinition::fromArray($key, $definition);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->themes[$key]);
    }

    public function get(string $key): ThemeDefinition
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(sprintf('Theme [%s] is not registered.', $key));
        }

        return $this->themes[$key];
    }

    /** @return array<string,ThemeDefinition> */
    public function all(): array
    {
        return $this->themes;
    }

    /** @return array<string,ThemeDefinition> */
    public function forContext(string $context): array
    {
        return array_filter(
            $this->themes,
            static fn (ThemeDefinition $definition): bool => $definition->context() === $context,
        );
    }
}
