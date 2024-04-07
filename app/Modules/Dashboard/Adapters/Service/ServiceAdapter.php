<?php

namespace App\Modules\Dashboard\Adapters\Service;

use App\Modules\Dashboard\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Domain\Entities\Objects\ServiceObject as ModuleServiceObject;

readonly class ServiceAdapter
{
    public function __construct(
        private FindServicesAction $findServicesAction
    ) {
    }

    /**
     * @return ServiceObject[]
     */
    public function find(): array
    {
        return array_map(
            fn(ModuleServiceObject $object) => new ServiceObject(
                id: $object->id,
                name: $object->name,
            ),
            $this->findServicesAction->handle()
        );
    }
}
