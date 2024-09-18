<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\CreateTraceAdminStoreActionInterface;
use App\Modules\Trace\Domain\Entities\Objects\TraceAdminStoreObject;
use App\Modules\Trace\Domain\Entities\Transports\TraceAdminStoreTransport;
use App\Modules\Trace\Repositories\Interfaces\TraceAdminStoreRepositoryInterface;

readonly class CreateTraceAdminStoreAction implements CreateTraceAdminStoreActionInterface
{
    public function __construct(
        private TraceAdminStoreRepositoryInterface $traceAdminStoreRepository
    ) {
    }

    public function handle(
        string $title,
        int $storeVersion,
        string $storeData
    ): TraceAdminStoreObject {
        $store = $this->traceAdminStoreRepository->create(
            title: $title,
            storeVersion: $storeVersion,
            storeDataHash: md5($storeData),
            storeData: $storeData
        );

        return TraceAdminStoreTransport::toObject($store);
    }
}
