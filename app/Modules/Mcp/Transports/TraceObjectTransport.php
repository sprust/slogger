<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Mcp\Entities\Trace\Data\TraceDataObject;
use App\Modules\Mcp\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Mcp\Entities\Trace\Groups\TraceGroupComparisonRowObject;
use App\Modules\Mcp\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Mcp\Entities\Trace\Groups\TraceGroupsObject;
use App\Modules\Mcp\Entities\Trace\TraceDataRangeObject;
use App\Modules\Mcp\Entities\Trace\TraceDetailObject;
use App\Modules\Mcp\Entities\Trace\TraceItemObject;
use App\Modules\Mcp\Entities\Trace\TraceItemObjects;
use App\Modules\Mcp\Entities\Trace\TraceItemTraceObject;
use App\Modules\Mcp\Entities\Trace\TraceServiceObject;
use App\Modules\Mcp\Entities\Trace\TraceStringFieldObject;
use App\Modules\Mcp\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject as SourceTraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject as SourceTraceDataObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonObject as SourceTraceGroupComparisonObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonRowObject as SourceTraceGroupComparisonRowObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject as SourceTraceGroupObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupsObject as SourceTraceGroupsObject;
use App\Modules\Trace\Entities\Trace\TraceDataRangeObject as SourceTraceDataRangeObject;
use App\Modules\Trace\Entities\Trace\TraceDetailObject as SourceTraceDetailObject;
use App\Modules\Trace\Entities\Trace\TraceItemObject as SourceTraceItemObject;
use App\Modules\Trace\Entities\Trace\TraceItemObjects as SourceTraceItemObjects;
use App\Modules\Trace\Entities\Trace\TraceItemTraceObject as SourceTraceItemTraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject as SourceTraceServiceObject;
use App\Modules\Trace\Entities\Trace\TraceStringFieldObject as SourceTraceStringFieldObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject as SourceTraceTreeRawObject;
use App\Modules\Mcp\Parameters\TraceTreeNodeParameters;

readonly class TraceObjectTransport
{
    public function fromTraceDataRangeObject(
        SourceTraceDataRangeObject $sourceTraceDataRangeObject
    ): TraceDataRangeObject {
        return new TraceDataRangeObject(
            firstHour: $sourceTraceDataRangeObject->firstHour,
            lastHour: $sourceTraceDataRangeObject->lastHour,
        );
    }

    public function fromTraceDetailObject(
        SourceTraceDetailObject $sourceTraceDetailObject
    ): TraceDetailObject {
        return new TraceDetailObject(
            id: $sourceTraceDetailObject->id,
            service: $sourceTraceDetailObject->service === null
                ? null
                : $this->fromTraceServiceObject($sourceTraceDetailObject->service),
            traceId: $sourceTraceDetailObject->traceId,
            parentTraceId: $sourceTraceDetailObject->parentTraceId,
            type: $sourceTraceDetailObject->type,
            status: $sourceTraceDetailObject->status,
            tags: $sourceTraceDetailObject->tags,
            data: $this->fromTraceDataObject($sourceTraceDetailObject->data),
            duration: $sourceTraceDetailObject->duration,
            memory: $sourceTraceDetailObject->memory,
            cpu: $sourceTraceDetailObject->cpu,
            pid: $sourceTraceDetailObject->pid,
            loggedAt: $sourceTraceDetailObject->loggedAt,
            createdAt: $sourceTraceDetailObject->createdAt,
            updatedAt: $sourceTraceDetailObject->updatedAt,
        );
    }

    public function fromTraceServiceObject(
        SourceTraceServiceObject $sourceTraceServiceObject
    ): TraceServiceObject {
        return new TraceServiceObject(
            id: $sourceTraceServiceObject->id,
            name: $sourceTraceServiceObject->name,
        );
    }

    public function fromTraceStringFieldObject(
        SourceTraceStringFieldObject $sourceTraceStringFieldObject
    ): TraceStringFieldObject {
        return new TraceStringFieldObject(
            name: $sourceTraceStringFieldObject->name,
            count: $sourceTraceStringFieldObject->count,
        );
    }

    public function fromTraceDataObject(
        SourceTraceDataObject $sourceTraceDataObject
    ): TraceDataObject {
        return new TraceDataObject(
            key: $sourceTraceDataObject->key,
            value: $sourceTraceDataObject->value,
            children: $sourceTraceDataObject->children === null ? null : array_map(
                fn(
                    SourceTraceDataObject $sourceTraceDataObject
                ): TraceDataObject => $this->fromTraceDataObject($sourceTraceDataObject),
                $sourceTraceDataObject->children
            ),
            filterKey: $sourceTraceDataObject->filterKey,
            canBeFiltered: $sourceTraceDataObject->canBeFiltered,
        );
    }

    public function fromTraceDataAdditionalFieldObject(
        SourceTraceDataAdditionalFieldObject $sourceTraceDataAdditionalFieldObject
    ): TraceDataAdditionalFieldObject {
        return new TraceDataAdditionalFieldObject(
            key: $sourceTraceDataAdditionalFieldObject->key,
            values: $sourceTraceDataAdditionalFieldObject->values,
        );
    }

    public function fromTraceItemObjects(
        SourceTraceItemObjects $sourceTraceItemObjects
    ): TraceItemObjects {
        return new TraceItemObjects(
            items: array_map(
                fn(
                    SourceTraceItemObject $sourceTraceItemObject
                ): TraceItemObject => $this->fromTraceItemObject($sourceTraceItemObject),
                $sourceTraceItemObjects->items
            ),
            paginationInfo: $sourceTraceItemObjects->paginationInfo,
        );
    }

    public function fromTraceItemObject(
        SourceTraceItemObject $sourceTraceItemObject
    ): TraceItemObject {
        return new TraceItemObject(
            trace: $this->fromTraceItemTraceObject($sourceTraceItemObject->trace),
        );
    }

    public function fromTraceItemTraceObject(
        SourceTraceItemTraceObject $sourceTraceItemTraceObject
    ): TraceItemTraceObject {
        return new TraceItemTraceObject(
            service: $sourceTraceItemTraceObject->service === null
                ? null
                : $this->fromTraceServiceObject($sourceTraceItemTraceObject->service),
            traceId: $sourceTraceItemTraceObject->traceId,
            parentTraceId: $sourceTraceItemTraceObject->parentTraceId,
            type: $sourceTraceItemTraceObject->type,
            status: $sourceTraceItemTraceObject->status,
            tags: $sourceTraceItemTraceObject->tags,
            duration: $sourceTraceItemTraceObject->duration,
            memory: $sourceTraceItemTraceObject->memory,
            cpu: $sourceTraceItemTraceObject->cpu,
            pid: $sourceTraceItemTraceObject->pid,
            additionalFields: array_map(
                fn(
                    SourceTraceDataAdditionalFieldObject $sourceTraceDataAdditionalFieldObject
                ): TraceDataAdditionalFieldObject => $this->fromTraceDataAdditionalFieldObject(
                    $sourceTraceDataAdditionalFieldObject
                ),
                $sourceTraceItemTraceObject->additionalFields
            ),
            loggedAt: $sourceTraceItemTraceObject->loggedAt,
            createdAt: $sourceTraceItemTraceObject->createdAt,
            updatedAt: $sourceTraceItemTraceObject->updatedAt,
        );
    }

    public function fromTraceGroupsObject(
        SourceTraceGroupsObject $sourceTraceGroupsObject
    ): TraceGroupsObject {
        return new TraceGroupsObject(
            items: array_map(
                fn(
                    SourceTraceGroupObject $sourceTraceGroupObject
                ): TraceGroupObject => $this->fromTraceGroupObject($sourceTraceGroupObject),
                $sourceTraceGroupsObject->items
            ),
            truncated: $sourceTraceGroupsObject->truncated,
        );
    }

    public function fromTraceGroupObject(
        SourceTraceGroupObject $sourceTraceGroupObject
    ): TraceGroupObject {
        return new TraceGroupObject(
            serviceId: $sourceTraceGroupObject->serviceId,
            type: $sourceTraceGroupObject->type,
            status: $sourceTraceGroupObject->status,
            startedAt: $sourceTraceGroupObject->startedAt,
            count: $sourceTraceGroupObject->count,
            durationAvg: $sourceTraceGroupObject->durationAvg,
            durationP95: $sourceTraceGroupObject->durationP95,
            durationMax: $sourceTraceGroupObject->durationMax,
            exampleTraceId: $sourceTraceGroupObject->exampleTraceId,
        );
    }

    public function fromTraceGroupComparisonObject(
        SourceTraceGroupComparisonObject $sourceTraceGroupComparisonObject
    ): TraceGroupComparisonObject {
        return new TraceGroupComparisonObject(
            groupATotal: $sourceTraceGroupComparisonObject->groupATotal,
            groupBTotal: $sourceTraceGroupComparisonObject->groupBTotal,
            rows: array_map(
                fn(
                    SourceTraceGroupComparisonRowObject $sourceTraceGroupComparisonRowObject
                ): TraceGroupComparisonRowObject => $this->fromTraceGroupComparisonRowObject(
                    $sourceTraceGroupComparisonRowObject
                ),
                $sourceTraceGroupComparisonObject->rows
            ),
            truncated: $sourceTraceGroupComparisonObject->truncated,
        );
    }

    public function fromTraceGroupComparisonRowObject(
        SourceTraceGroupComparisonRowObject $sourceTraceGroupComparisonRowObject
    ): TraceGroupComparisonRowObject {
        return new TraceGroupComparisonRowObject(
            value: $sourceTraceGroupComparisonRowObject->value,
            groupACount: $sourceTraceGroupComparisonRowObject->groupACount,
            groupAShare: $sourceTraceGroupComparisonRowObject->groupAShare,
            groupBCount: $sourceTraceGroupComparisonRowObject->groupBCount,
            groupBShare: $sourceTraceGroupComparisonRowObject->groupBShare,
        );
    }

    public function toTraceTreeNodeParameters(
        SourceTraceTreeRawObject $sourceTraceTreeRawObject
    ): TraceTreeNodeParameters {
        return new TraceTreeNodeParameters(
            serviceId: $sourceTraceTreeRawObject->serviceId,
            traceId: $sourceTraceTreeRawObject->traceId,
            parentTraceId: $sourceTraceTreeRawObject->parentTraceId,
            type: $sourceTraceTreeRawObject->type,
            tags: $sourceTraceTreeRawObject->tags,
            status: $sourceTraceTreeRawObject->status,
            duration: $sourceTraceTreeRawObject->duration,
            memory: $sourceTraceTreeRawObject->memory,
            cpu: $sourceTraceTreeRawObject->cpu,
            pid: $sourceTraceTreeRawObject->pid,
            loggedAt: $sourceTraceTreeRawObject->loggedAt,
        );
    }

    public function fromTraceTreeNodeParameters(
        TraceTreeNodeParameters $traceTreeNodeParameters
    ): TraceTreeRawObject {
        return new TraceTreeRawObject(
            serviceId: $traceTreeNodeParameters->serviceId,
            traceId: $traceTreeNodeParameters->traceId,
            parentTraceId: $traceTreeNodeParameters->parentTraceId,
            type: $traceTreeNodeParameters->type,
            tags: $traceTreeNodeParameters->tags,
            status: $traceTreeNodeParameters->status,
            duration: $traceTreeNodeParameters->duration,
            memory: $traceTreeNodeParameters->memory,
            cpu: $traceTreeNodeParameters->cpu,
            pid: $traceTreeNodeParameters->pid,
            loggedAt: $traceTreeNodeParameters->loggedAt,
        );
    }
}
