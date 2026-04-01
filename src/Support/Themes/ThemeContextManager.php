<?php
declare(strict_types=1);

namespace Dominservice\LaravelThemeHelper\Support\Themes;

use InvalidArgumentException;

final class ThemeContextManager
{
    /** @var array<string,string> */
    private array $defaults = [];

    /** @var array<string,string> */
    private array $active = [];

    /** @param array<string,array<string,mixed>> $contexts */
    public function __construct(
        private readonly ThemeRegistry $registry,
        array $contexts = [],
    ) {
        foreach ($contexts as $context => $config) {
            $defaultTheme = (string) ($config['default_theme'] ?? '');
            if ($defaultTheme !== '') {
                $this->defaults[$context] = $defaultTheme;
                $this->active[$context] = $defaultTheme;
            }
        }
    }

    public function use(string $themeKey, ?string $context = null): self
    {
        $theme = $this->registry->get($themeKey);
        $resolvedContext = $context ?: $theme->context();

        if ($context !== null && $theme->context() !== $context) {
            throw new InvalidArgumentException(sprintf(
                'Theme [%s] belongs to context [%s], not [%s].',
                $themeKey,
                $theme->context(),
                $context,
            ));
        }

        $this->active[$resolvedContext] = $themeKey;

        return $this;
    }

    public function current(string $context = 'public'): ThemeDefinition
    {
        $themeKey = $this->active[$context] ?? $this->defaults[$context] ?? null;
        if ($themeKey === null || $themeKey === '') {
            throw new InvalidArgumentException(sprintf('No active theme configured for context [%s].', $context));
        }

        return $this->registry->get($themeKey);
    }

    public function currentKey(string $context = 'public'): string
    {
        return $this->current($context)->key();
    }

    /** @return array<string,string> */
    public function activeThemes(): array
    {
        return $this->active;
    }
}
