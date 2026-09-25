<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Logs\Domain\Actions\FindLogEntriesAction;
use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Infrastructure\Http\Requests\FindLogEntriesRequest;
use App\Modules\Logs\Infrastructure\Http\Resources\LogEntriesPageResource;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class LogEntryController
{
    public function __construct(
        private FindLogEntriesAction $findLogEntriesAction
    ) {
    }

    public function index(FindLogEntriesRequest $request): LogEntriesPageResource
    {
        $validated = $request->validated();

        $from = ArrayValueGetter::stringNull($validated, 'from');
        $to   = ArrayValueGetter::stringNull($validated, 'to');

        $direction = ArrayValueGetter::stringNull($validated, 'direction');
        $levelKeys = ArrayValueGetter::arrayStringNull($validated, 'levels');

        try {
            $page = $this->findLogEntriesAction->handle(
                new FindLogEntriesParameters(
                    fileIds: array_values(ArrayValueGetter::arrayStringNull($validated, 'files') ?? []),
                    levelKeys: $levelKeys === null ? null : array_values($levelKeys),
                    fromTime: $from === null ? null : Carbon::parse($from)->getTimestamp(),
                    toTime: $to === null ? null : Carbon::parse($to)->getTimestamp(),
                    searchQuery: ArrayValueGetter::stringNull($validated, 'search_query'),
                    cursor: ArrayValueGetter::stringNull($validated, 'cursor'),
                    direction: $direction === null ? LogCursorDirectionEnum::Older : LogCursorDirectionEnum::from($direction),
                    perPage: ArrayValueGetter::intNull($validated, 'per_page') ?? (int) config('module-logs.search.per_page')
                )
            );
        } catch (LogCursorInvalidException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        return new LogEntriesPageResource($page);
    }
}
