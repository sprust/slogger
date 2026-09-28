<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Domain\Exceptions\McpTraceInvalidPeriodException;
use App\Modules\Mcp\Domain\Exceptions\McpTracePeriodTooWideException;
use App\Modules\Mcp\Entities\McpTracePeriodObject;
use Illuminate\Support\Carbon;

readonly class McpTracePeriodResolver
{
    public const int MAX_HOURS = 24;

    /**
     * @throws McpTraceInvalidPeriodException
     * @throws McpTracePeriodTooWideException
     */
    public function resolve(Carbon $from, Carbon $to): McpTracePeriodObject
    {
        if ($from->gt($to)) {
            throw new McpTraceInvalidPeriodException();
        }

        $alignedFrom = $from->clone()->utc()->startOfHour();
        $alignedTo   = $to->clone()->utc()->startOfHour();

        if ($alignedTo->lt($to)) {
            $alignedTo->addHour();
        }

        if ($alignedTo->eq($alignedFrom)) {
            $alignedTo->addHour();
        }

        if ($alignedFrom->diffInHours($alignedTo) > self::MAX_HOURS) {
            throw new McpTracePeriodTooWideException(self::MAX_HOURS);
        }

        return new McpTracePeriodObject(
            from: $alignedFrom,
            to: $alignedTo
        );
    }
}
