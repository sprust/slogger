<?php

namespace App\Modules\Service\Api;

use App\Modules\Service\Http\ServiceContainer;
use App\Modules\Service\Services\Objects\ServiceObject;

class ServiceApi
{
    public function __construct(
        public ServiceContainer $serviceContainer
    ) {
    }

    public function getCurrentService(): ?ServiceObject
    {
        return $this->serviceContainer->getService();
    }
}
