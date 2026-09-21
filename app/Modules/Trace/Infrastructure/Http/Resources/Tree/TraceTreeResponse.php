<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractStreamedApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;

class TraceTreeResponse extends AbstractStreamedApiResource
{
    /**
     * How many nodes are encoded before they are written out.
     *
     * One write per node was a write through the output buffer per node, and a JsonResource
     * with its reflection per node before that: on three hundred thousand nodes the two
     * together cost more than reading them.
     */
    private const int NODES_PER_WRITE = 1000;

    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    public function __construct(TraceTreeResultObject $resource)
    {
        parent::__construct(
            callback: static function () use ($resource) {
                echo '{"data":{"state":' . new TraceTreeStateResource($resource->state)->toJson()
                    . ',"lazy":' . ($resource->lazy ? 'true' : 'false')
                    . ',"items":';

                if ($resource->items === null) {
                    echo 'null}}';

                    return;
                }

                echo '[';

                $encoded = [];

                $written = false;

                foreach ($resource->items as $item) {
                    $encoded[] = json_encode(self::node($item), self::JSON_FLAGS);

                    if (count($encoded) < self::NODES_PER_WRITE) {
                        continue;
                    }

                    echo ($written ? ',' : '') . implode(',', $encoded);

                    $written = true;

                    $encoded = [];
                }

                if ($encoded !== []) {
                    echo ($written ? ',' : '') . implode(',', $encoded);
                }

                echo ']}}';
            },
        );
    }

    /**
     * One node in exactly the shape TraceTreeResource describes — that class is what the
     * OpenAPI schema and the panel's types are generated from, and the test beside this one
     * holds the two to the same keys and values.
     *
     * @return array<string, mixed>
     */
    public static function node(TraceTreeRawObject $item): array
    {
        return [
            'service_id'      => $item->serviceId ?: -1,
            'parent_trace_id' => $item->parentTraceId,
            'trace_id'        => $item->traceId,
            'type'            => $item->type,
            'tags'            => $item->tags,
            'status'          => $item->status,
            'duration'        => $item->duration,
            'memory'          => $item->memory,
            'cpu'             => $item->cpu,
            'logged_at'       => $item->loggedAt->toDateTimeString('microsecond'),
        ];
    }
}
