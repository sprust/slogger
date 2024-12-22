<?php

declare(strict_types=1);

namespace App\Modules\Tools\Infrastructure\Services\Objects;

readonly class ToolLinksObject
{
    public function __construct(
        public string $name,
        public string $url
    ) {
    }
}
