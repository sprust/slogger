<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

readonly class McpToolProperty
{
    /**
     * @param string[]|null $enum
     */
    public function __construct(
        public string $name,
        public McpToolPropertyTypeEnum $type,
        public string $description,
        public bool $required = false,
        public ?array $enum = null,
        public ?int $min = null,
        public ?int $max = null
    ) {
    }
}
