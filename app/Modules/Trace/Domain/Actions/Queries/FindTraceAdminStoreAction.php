<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceAdminStoreActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceAdminStoreRepositoryInterface;
use App\Modules\Trace\Entities\Store\TraceAdminStoresPaginationObject;

readonly class FindTraceAdminStoreAction implements FindTraceAdminStoreActionInterface
{
    private int $perPage;

    public function __construct(
        private TraceAdminStoreRepositoryInterface $traceAdminStoreRepository
    ) {
        $this->perPage = 30;
    }

    public function handle(
        int $page,
        int $version,
        ?string $searchQuery,
        bool $auto
    ): TraceAdminStoresPaginationObject {
        return $this->traceAdminStoreRepository->find(
            page: $page,
            perPage: $this->perPage,
            version: $version,
            searchQuery: $searchQuery,
            auto: $auto
        );
    }
}
