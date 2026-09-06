<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Broadcasting;

use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * A snapshot of what the dynamic indexes are doing, taken once by the task pool instead
 * of once every two seconds by every open tab.
 *
 * The progress of a build is not a domain fact but a reading — getIndexProgressesInfo()
 * asks Mongo what it is doing right now, and the answer is the same for everyone
 * watching. So this is published by the pool that does the building, and no domain event
 * stands behind it.
 *
 * ShouldRescue: a reading nobody could be told about is not a failed tick.
 */
class TraceDynamicIndexStatsBroadcast implements ShouldBroadcastNow, ShouldRescue
{
    public function __construct(
        private readonly TraceDynamicIndexStatsObject $stats,
    ) {
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sl-trace-indexes'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stats.updated';
    }

    /**
     * The keys of TraceDynamicIndexStatsResource, so the panel keeps typing this as the
     * stats endpoint's `data`. Asserted by TraceDynamicIndexBroadcastsTest.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'in_process_count'   => $this->stats->inProcessCount,
            'errors_count'       => $this->stats->errorsCount,
            'total_count'        => $this->stats->totalCount,
            'indexes_in_process' => array_map(
                static fn(TraceIndexInfoObject $info): array => [
                    'collectionName' => $info->collectionName,
                    'name'           => $info->name,
                    'progress'       => $info->progress,
                ],
                $this->stats->indexesInProcess
            ),
        ];
    }
}
