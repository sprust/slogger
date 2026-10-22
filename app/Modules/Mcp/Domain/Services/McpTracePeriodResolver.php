<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Domain\Exceptions\McpTraceInvalidPeriodException;
use App\Modules\Mcp\Domain\Exceptions\McpTracePeriodTooWideException;
use App\Modules\Mcp\Entities\McpTracePeriodObject;
use Illuminate\Support\Carbon;

/**
 * The period of a trace query, taken exactly as asked: `from` inclusive, `to` exclusive.
 *
 * It may span the whole time traces are kept for and no more — a longer one would ask
 * for hours that are no longer there.
 */
readonly class McpTracePeriodResolver
{
    /**
     * @throws McpTraceInvalidPeriodException
     * @throws McpTracePeriodTooWideException
     */
    public function resolve(Carbon $from, Carbon $to, int $maxHours): McpTracePeriodObject
    {
        $from = $from->clone()->utc();
        $to   = $to->clone()->utc();

        if ($from->gte($to)) {
            throw new McpTraceInvalidPeriodException();
        }

        if ($from->clone()->addHours($maxHours)->lt($to)) {
            throw new McpTracePeriodTooWideException($maxHours);
        }

        return new McpTracePeriodObject(
            from: $from,
            to: $to
        );
    }
}
