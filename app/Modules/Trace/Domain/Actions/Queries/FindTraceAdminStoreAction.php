<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Common\Domain\Transports\PaginationInfoTransport;
use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceAdminStoreActionInterface;
use App\Modules\Trace\Domain\Entities\Objects\TraceAdminStoresPaginationObject;
use App\Modules\Trace\Domain\Entities\Transports\TraceAdminStoreTransport;
use App\Modules\Trace\Repositories\Dto\TraceAdminStoreDto;
use App\Modules\Trace\Repositories\Interfaces\TraceAdminStoreRepositoryInterface;

readonly class FindTraceAdminStoreAction implements FindTraceAdminStoreActionInterface
{
    private int $perPage;

    public function __construct(
        private TraceAdminStoreRepositoryInterface $traceAdminStoreRepository
    ) {
        $this->perPage = 30;
    }

    public function handle(int $page, ?string $searchQuery = null): TraceAdminStoresPaginationObject
    {
        $pagination = $this->traceAdminStoreRepository->find(
            page: $page,
            perPage: $this->perPage,
            searchQuery: $searchQuery
        );

        return new TraceAdminStoresPaginationObject(
            items: array_map(
                fn(TraceAdminStoreDto $dto) => TraceAdminStoreTransport::toObject($dto),
                $pagination->items
            ),
            paginationInfo: PaginationInfoTransport::toObject(
                $pagination->paginationInfo
            )
        );
    }
}
