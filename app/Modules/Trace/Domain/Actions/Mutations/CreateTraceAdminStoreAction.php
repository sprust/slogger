<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;
use App\Modules\Trace\Repositories\TraceAdminStoreRepository;

readonly class CreateTraceAdminStoreAction
{
    public function __construct(
        private TraceAdminStoreRepository $traceAdminStoreRepository
    ) {
    }

    public function handle(
        string $title,
        int $storeVersion,
        string $storeData,
        bool $auto
    ): TraceAdminStoreObject {
        return $this->traceAdminStoreRepository->create(
            title: $title,
            storeVersion: $storeVersion,
            storeDataHash: md5($storeData),
            storeData: $storeData,
            auto: $auto
        );
    }
}
