<?php

namespace App\Modules\Trace\Domain\Entities\Transports;

use App\Modules\Trace\Domain\Entities\Objects\ProcessObject;
use App\Modules\Trace\Repositories\Dto\ProcessDto;

class ProcessTransport
{
    public static function toObject(ProcessDto $dto): ProcessObject
    {
        return new ProcessObject(
            id: $dto->id,
            settingId: $dto->settingId,
            clearedCount: $dto->clearedCount,
            clearedAt: $dto->clearedAt,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt,
        );
    }
}
