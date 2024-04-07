<?php

namespace App\Modules\TraceCleaner\Domain\Entities\Transports;

use App\Modules\TraceCleaner\Domain\Entities\Objects\ProcessObject;
use App\Modules\TraceCleaner\Repositories\Dto\ProcessDto;

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
