<?php

namespace App\Modules\Service\Framework\Http;

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
