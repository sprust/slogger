<?php

declare(strict_types=1);

namespace App\Modules\Service\Entities;

readonly class ServiceObject
{
    public function __construct(
        public int $id,
        public string $name,
        public string $apiToken
    ) {
    }
}
