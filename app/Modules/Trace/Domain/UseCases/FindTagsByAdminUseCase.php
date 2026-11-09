<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;
use App\Modules\Trace\Parameters\TraceFindTagsParameters;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Trace\Domain\Actions\Queries\FindTagsAction;

readonly class FindTagsByAdminUseCase
{
    public function __construct(private FindTagsAction $findTagsAction)
    {
    }

    /**
     * @return TraceStringFieldObject[]
     *
     * @throws ClickhouseQueryException
     */
    public function handle(TraceFindTagsParameters $parameters): array
    {
        return $this->findTagsAction->handle(
            parameters: $parameters,
        );
    }
}
