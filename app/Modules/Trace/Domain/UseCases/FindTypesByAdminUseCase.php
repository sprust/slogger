<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindTypesParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTypesAction;

readonly class FindTypesByAdminUseCase
{
    public function __construct(private FindTypesAction $findTypesAction)
    {
    }

    /**
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function handle(TraceFindTypesParameters $parameters): array
    {
        return $this->findTypesAction->handle($parameters);
    }
}
