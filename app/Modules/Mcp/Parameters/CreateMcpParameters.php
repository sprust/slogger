<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Parameters;

readonly class CreateMcpParameters
{
    public function __construct(
        public string $name
    ) {
    }
}
