<?php

namespace App\Modules\Service\Http;

use App\Models\Services\Service;

class ServiceContainer
{
    private ?Service $service = null;

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): void
    {
        $this->service = $service;
    }
}
