<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractStreamedApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;
use Generator;

class TraceTreeResponse extends AbstractStreamedApiResource
{
    /**
     * How many nodes go into one chunk.
     *
     * A chunk per node would be a write per node, and a JSON document is not readable before
     * its last byte anyway; a thousand nodes are some two hundred kilobytes on the wire.
     */
    private const int NODES_PER_CHUNK = 1000;

    private const int JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    public function __construct(TraceTreeResultObject $resource)
    {
        parent::__construct(
            chunks: self::chunks($resource),
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

    /**
     * The body, a chunk at a time — read lazily, so the tree is taken from the cache while
     * the previous chunks are already on their way.
     *
     * @return Generator<int, string>
     */
    private static function chunks(TraceTreeResultObject $resource): Generator
    {
        yield '{"data":{"state":' . new TraceTreeStateResource($resource->state)->toJson()
            . ',"lazy":' . ($resource->lazy ? 'true' : 'false')
            . ',"items":';

        if ($resource->items === null) {
            yield 'null}}';

            return;
        }

        yield '[';

        $encoded = [];

        $written = false;

        foreach ($resource->items as $item) {
            $encoded[] = json_encode(self::node($item), self::JSON_FLAGS);

            if (count($encoded) < self::NODES_PER_CHUNK) {
                continue;
            }

            yield ($written ? ',' : '') . implode(',', $encoded);

            $written = true;

            $encoded = [];
        }

        if ($encoded !== []) {
            yield ($written ? ',' : '') . implode(',', $encoded);
        }

        yield ']}}';
    }
}
