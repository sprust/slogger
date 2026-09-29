<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

readonly class UpdateMcpParameters
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $enabled
    ) {
    }
}
