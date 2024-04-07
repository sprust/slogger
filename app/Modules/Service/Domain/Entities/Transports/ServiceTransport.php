<?php

namespace App\Modules\Service\Domain\Entities\Transports;

use App\Modules\Service\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Repositories\Dto\ServiceDto;

class ServiceTransport
{
    public static function toObject(ServiceDto $dto): ServiceObject
    {
        return new ServiceObject(
            id: $dto->id,
            name: $dto->name,
            apiToken: $dto->apiToken
        );
    }
}
