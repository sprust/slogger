<?php

namespace App\Modules\Cleaner\Domain\Entities\Transports;

use App\Modules\Cleaner\Domain\Entities\Objects\ProcessObject;
use App\Modules\Cleaner\Repositories\Dto\ProcessDto;

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
