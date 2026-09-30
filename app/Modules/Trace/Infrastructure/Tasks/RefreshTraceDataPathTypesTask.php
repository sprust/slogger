<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Tasks;

use App\Modules\Trace\Domain\Actions\Mutations\RefreshTraceDataPathTypesAction;
use App\Services\Clickhouse\ClickhouseQueryException;
use SConcur\Laravel\Tasks\TaskInterface;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * Keeps the map of the array paths of the trace data fresh, so that no search waits for
 * it: once when the pool starts, then every five minutes.
 *
 * It ticks more often than that and watches the time itself, as CheckWatchersTask
 * watches the minute. A refresh that throws leaves the time as it was, and the next tick
 * after the pool's backoff tries again.
 */
class RefreshTraceDataPathTypesTask implements TaskInterface
{
    public const string NAME = 'trace-data-paths';

    public const int INTERVAL_SECONDS = 300;

    private ?int $refreshedAt = null;

    public function __construct(
        private readonly RefreshTraceDataPathTypesAction $refreshAction,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function tick(): TickResultEnum
    {
        $now = time();

        if (!is_null($this->refreshedAt) && $now - $this->refreshedAt < self::INTERVAL_SECONDS) {
            return TickResultEnum::Idle;
        }

        $this->refreshAction->handle();

        $this->refreshedAt = $now;

        return TickResultEnum::Worked;
    }
}
