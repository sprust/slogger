<?php

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Domain\Services\ServiceContainer;

readonly class GetCurrentServiceAction
{
    public function __construct(
        private ServiceContainer $serviceContainer
    ) {
    }

    public function handle(): ?ServiceObject
    {
        return $this->serviceContainer->getService();
    }
}
