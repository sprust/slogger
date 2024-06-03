<?php

namespace App\Modules\Service\Domain\Entities\Parameters;

readonly class ServiceCreateParameters
{
    public function __construct(public string $name)
    {
    }
}
