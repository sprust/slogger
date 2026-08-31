<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Tasks;

use App\Modules\Trace\Domain\Actions\Mutations\BuildPendingTraceDynamicIndexesAction;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteExpiredTraceDynamicIndexesAction;
use SConcur\Laravel\Tasks\TaskInterface;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * Keeps the dynamic trace indexes in shape: builds what was requested and drops what has
 * expired.
 *
 * Both passes go through asynchronous Mongo, so this tick suspends its coroutine on
 * every operation and the rest of the pool keeps running — including while a large index
 * is being built, which createIndex() itself fans out across collections in a nested
 * WaitGroup.
 *
 * The expiry pass is the cheaper of the two and does not need to run as often, so it
 * carries its own timer. That timer is the only state this task holds, and dropping it
 * is all a restart of this task does.
 */
class BuildTraceDynamicIndexesTask implements TaskInterface
{
    public const string NAME = 'trace-dynamic-indexes';

    private const int DELETE_EXPIRED_INTERVAL_SECONDS = 30;

    private int $deleteExpiredAt = 0;

    public function __construct(
        private readonly BuildPendingTraceDynamicIndexesAction $buildPendingAction,
        private readonly DeleteExpiredTraceDynamicIndexesAction $deleteExpiredAction,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function tick(): TickResultEnum
    {
        $handled = 0;

        if ($this->deleteExpiredAt <= time()) {
            $handled += $this->deleteExpiredAction->handle();

            $this->deleteExpiredAt = time() + self::DELETE_EXPIRED_INTERVAL_SECONDS;
        }

        $handled += $this->buildPendingAction->handle();

        // Work found means there is probably more behind it: saying so lets the pool
        // take the next batch immediately instead of idling a second between them.
        return $handled > 0 ? TickResultEnum::Worked : TickResultEnum::Idle;
    }
}
