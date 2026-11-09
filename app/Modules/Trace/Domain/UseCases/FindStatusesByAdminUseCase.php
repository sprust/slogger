<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindStatusesParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindStatusesAction;

readonly class FindStatusesByAdminUseCase
{
    public function __construct(private FindStatusesAction $findStatusesAction)
    {
    }

    /**
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function handle(TraceFindStatusesParameters $parameters): array
    {
        return $this->findStatusesAction->handle(
            parameters: $parameters,
        );
    }
}
