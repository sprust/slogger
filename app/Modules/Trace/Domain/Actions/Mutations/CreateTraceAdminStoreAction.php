<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\Mutations\CreateTraceAdminStoreActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceAdminStoreRepositoryInterface;
use App\Modules\Trace\Entities\Store\TraceAdminStoreObject;
use App\Modules\Trace\Transports\TraceAdminStoreTransport;

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
