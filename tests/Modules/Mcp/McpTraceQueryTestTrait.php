<?php

namespace Tests\Modules\Mcp;

use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Mcp\Entities\McpTracePeriodObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Entities\Trace\TraceItemTraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use Illuminate\Support\Carbon;

trait McpTraceQueryTestTrait
{
    private function period(): McpTracePeriodObject
    {
        return new McpTracePeriodObject(
            from: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            to: Carbon::parse('2026-09-28 13:00:00', 'UTC')
        );
    }

    /**
     * @param string[]                         $traceIds
     * @param TraceDataAdditionalFieldObject[] $additionalFields
     */
    private function traceItems(array $traceIds, array $additionalFields = []): TraceItemObjects
    {
        $loggedAt = Carbon::parse('2026-09-28 12:30:00', 'UTC');

        return new TraceItemObjects(
            items: array_map(
                static fn(string $traceId) => new TraceItemObject(
                    trace: new TraceItemTraceObject(
                        service: new TraceServiceObject(id: 2, name: 'pms'),
                        traceId: $traceId,
                        parentTraceId: 'parent-1',
                        type: 'request',
                        status: 'failed',
                        tags: ['api'],
                        duration: 1.5,
                        memory: 12.0,
                        cpu: 0.4,
                        additionalFields: $additionalFields,
                        loggedAt: $loggedAt,
                        createdAt: $loggedAt,
                        updatedAt: $loggedAt
                    )
                ),
                $traceIds
            ),
            paginationInfo: new PaginationInfoObject(total: 0, perPage: 20, currentPage: 1)
        );
    }

    /**
     * @param TraceDataObject[] $children
     */
    private function detailWithData(string $traceId, array $children): TraceDetailObject
    {
        $loggedAt = Carbon::parse('2026-09-28 12:30:00', 'UTC');

        return new TraceDetailObject(
            id: "id-$traceId",
            service: new TraceServiceObject(id: 2, name: 'pms'),
            traceId: $traceId,
            parentTraceId: null,
            type: 'request',
            status: 'success',
            tags: [],
            data: new TraceDataObject(key: '', value: null, children: $children, canBeFiltered: false),
            duration: 0.5,
            memory: 1.0,
            cpu: 0.1,
            loggedAt: $loggedAt,
            createdAt: $loggedAt,
            updatedAt: $loggedAt
        );
    }

    /**
     * @param TraceDataObject[]|null $children
     */
    private function dataNode(string $key, string|int|float|bool|null $value = null, ?array $children = null): TraceDataObject
    {
        return new TraceDataObject(key: $key, value: $value, children: $children, canBeFiltered: true);
    }
}
