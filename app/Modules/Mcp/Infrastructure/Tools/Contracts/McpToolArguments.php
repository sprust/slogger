<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

readonly class McpToolArguments
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values
    ) {
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values) && !is_null($this->values[$name]);
    }

    public function string(string $name): string
    {
        return (string) $this->values[$name];
    }

    public function stringNull(string $name): ?string
    {
        return $this->has($name) ? (string) $this->values[$name] : null;
    }

    public function intNull(string $name): ?int
    {
        return $this->has($name) ? (int) $this->values[$name] : null;
    }

    /**
     * @return string[]
     */
    public function stringList(string $name): array
    {
        if (!$this->has($name) || !is_array($this->values[$name])) {
            return [];
        }

        return array_values(
            array_map(static fn(mixed $value) => (string) $value, $this->values[$name])
        );
    }

    /**
     * @return int[]
     */
    public function intList(string $name): array
    {
        if (!$this->has($name) || !is_array($this->values[$name])) {
            return [];
        }

        return array_values(
            array_map(static fn(mixed $value) => (int) $value, $this->values[$name])
        );
    }
}
