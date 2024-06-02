<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\SettingObject;
use App\Modules\Trace\Repositories\Dto\SettingDto;

class SettingTransport
{
    public static function toObject(SettingDto $dto): SettingObject
    {
        return new SettingObject(
            id: $dto->id,
            daysLifetime: $dto->daysLifetime,
            type: $dto->type,
            deleted: $dto->deleted,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }
}
