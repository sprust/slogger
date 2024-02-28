<?php

namespace App\Modules\Services\Http;

use App\Models\Services\Service;

class RequestServiceContainer
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
