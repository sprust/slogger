<?php

namespace App\Modules\Trace\Transports;

use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;
use App\Modules\Trace\Repositories\Dto\TraceAdminStoreDto;

class TraceAdminStoreTransport
{
    public static function toObject(TraceAdminStoreDto $dto): TraceAdminStoreObject
    {
        return new TraceAdminStoreObject(
            id: $dto->id,
            title: $dto->title,
            storeVersion: $dto->storeVersion,
            storeDataHash: $dto->storeDataHash,
            storeData: $dto->storeData,
            createdAt: $dto->createdAt,
        );
    }
}
