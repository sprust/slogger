<?php

namespace App\Modules\Service\Domain\Services;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;

class ServiceContainer
{
    private ?ServiceObject $service = null;

    public function getService(): ?ServiceObject
    {
        return $this->service;
    }

    public function setService(?ServiceObject $service): void
    {
        $this->service = $service;
    }
}
