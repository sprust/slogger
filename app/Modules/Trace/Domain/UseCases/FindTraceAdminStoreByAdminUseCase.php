<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Store\TraceAdminStoresPaginationObject;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceAdminStoreAction;

readonly class FindTraceAdminStoreByAdminUseCase
{
    public function __construct(
        private FindTraceAdminStoreAction $findTraceAdminStoreAction
    ) {
    }

    public function handle(
        int $page,
        int $version,
        ?string $searchQuery,
        bool $auto
    ): TraceAdminStoresPaginationObject {
        return $this->findTraceAdminStoreAction->handle(
            page: $page,
            version: $version,
            searchQuery: $searchQuery,
            auto: $auto,
        );
    }
}
