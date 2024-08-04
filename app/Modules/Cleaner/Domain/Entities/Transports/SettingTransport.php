<?php

namespace App\Modules\Cleaner\Domain\Entities\Transports;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Repositories\Dto\SettingDto;

class SettingTransport
{
    public static function toObject(SettingDto $dto): SettingObject
    {
        return new SettingObject(
            id: $dto->id,
            daysLifetime: $dto->daysLifetime,
            type: $dto->type,
            onlyData: $dto->onlyData,
            deleted: $dto->deleted,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }
}
