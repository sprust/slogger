<?php

namespace App\Modules\TraceCleaner\Domain\Entities\Transports;

use App\Modules\TraceCleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\TraceCleaner\Repositories\Dto\SettingDto;

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
