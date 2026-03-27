<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Store\TraceAdminStoresPaginationObject;
use App\Modules\Trace\Repositories\TraceAdminStoreRepository;

readonly class FindTraceAdminStoreAction
{
    private int $perPage;

    public function __construct(
        private TraceAdminStoreRepository $traceAdminStoreRepository
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
