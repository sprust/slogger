<?php

namespace App\Modules\Logs\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Logs\Domain\Actions\PaginateLogsAction;
use App\Modules\Logs\Infrastructure\Http\Requests\IndexLogsRequest;
use App\Modules\Logs\Infrastructure\Http\Resources\LogsPaginationResource;
use App\Modules\Logs\Parameters\FindLogsParameters;

readonly class LogController
{
    public function __construct(
        protected PaginateLogsAction $paginateLogsAction
    ) {
    }

    public function index(IndexLogsRequest $request): LogsPaginationResource
    {
        $validated = $request->validated();

        $pagination = $this->paginateLogsAction->handle(
            page: $validated['page'],
            parameters: new FindLogsParameters(
                searchQuery: ArrayValueGetter::stringNull($validated, 'search_query'),
                levels: ArrayValueGetter::arrayStringNull($validated, 'levels'),
            )
        );

        return new LogsPaginationResource($pagination);
    }
}
