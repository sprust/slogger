<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Controllers;

use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Logs\Domain\UseCases\FindLogEntriesByAdminUseCase;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Infrastructure\Http\Requests\FindLogEntriesRequest;
use App\Modules\Logs\Infrastructure\Http\Resources\LogEntriesPageResource;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use Illuminate\Support\Carbon;

readonly class LogEntryController
{
    public function __construct(
        private FindLogEntriesByAdminUseCase $findLogEntriesByAdminUseCase
    ) {
    }

    /**
     * @throws LogCursorInvalidException
     */
    public function index(FindLogEntriesRequest $request): LogEntriesPageResource
    {
        $validated = $request->validated();
        $from      = ArrayValueGetter::stringNull(
            data: $validated,
            key: 'from',
        );
        $to = ArrayValueGetter::stringNull(
            data: $validated,
            key: 'to',
        );
        $direction = ArrayValueGetter::stringNull(
            data: $validated,
            key: 'direction',
        );
        $levelKeys = ArrayValueGetter::arrayStringNull(
            data: $validated,
            key: 'levels',
        );
        $page = $this->findLogEntriesByAdminUseCase->handle(
            new FindLogEntriesParameters(
                fileIds: array_values(
                    ArrayValueGetter::arrayStringNull(
                        data: $validated,
                        key: 'files',
                    ) ?? []
                ),
                levelKeys: $levelKeys === null ? null : array_values($levelKeys),
                fromTime: $from === null ? null : Carbon::parse(
                    time: $from,
                )
                    ->getTimestamp(),
                toTime: $to === null ? null : Carbon::parse(
                    time: $to,
                )
                    ->getTimestamp(),
                searchQuery: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'search_query',
                ),
                cursor: ArrayValueGetter::stringNull(
                    data: $validated,
                    key: 'cursor',
                ),
                direction: $direction === null
                    ? LogCursorDirectionEnum::Older
                    : LogCursorDirectionEnum::from($direction),
                perPage: ArrayValueGetter::intNull(
                    data: $validated,
                    key: 'per_page',
                ) ?? (int) config(
                    key: 'module-logs.search.per_page',
                ),
            )
        );

        return new LogEntriesPageResource($page);
    }
}
