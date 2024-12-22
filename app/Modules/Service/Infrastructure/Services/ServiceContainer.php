<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Services;

use App\Modules\Service\Entities\ServiceObject;

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
