<?php

namespace App\Modules\Service\Http;

use App\Modules\Service\Services\Objects\ServiceObject;

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
