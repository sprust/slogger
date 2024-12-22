<?php

declare(strict_types=1);

namespace App\Modules\Service\Parameters;

readonly class ServiceCreateParameters
{
    public function __construct(public string $name)
    {
    }
}
