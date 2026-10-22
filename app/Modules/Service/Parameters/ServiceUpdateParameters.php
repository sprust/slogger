<?php

declare(strict_types=1);

namespace App\Modules\Service\Parameters;

readonly class ServiceUpdateParameters
{
    public function __construct(
        public int $id,
        public string $name
    ) {
    }
}
