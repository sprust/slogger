<?php

namespace App\Modules\Dashboard\Domain\Entities\Transports;

use App\Modules\Dashboard\Domain\Entities\Objects\ServiceObject;
use App\Modules\Dashboard\Domain\Entities\Objects\ServiceStatObject;
use App\Modules\Dashboard\Repositories\Dto\ServiceStatDto;

class ServiceStatTransport
{
    public static function toObject(ServiceStatDto $dto, ?ServiceObject $service): ServiceStatObject
    {
        return new ServiceStatObject(
            service: $service
                ?: new ServiceObject(
                    id: $dto->serviceId,
                    name: $service?->name ?? 'UNKNOWN',
                ),
            from: $dto->from,
            to: $dto->to,
            type: $dto->type,
            status: $dto->status,
            count: $dto->count,
        );
    }
}
