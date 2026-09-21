<?php

namespace Tests\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeResultObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeResponse;
use ArrayIterator;
use Illuminate\Support\Carbon;
use SConcur\Laravel\Http\StreamedChunks;
use Tests\TestCase;

/**
 * The tree endpoint writes its nodes by hand, and the panel types them by TraceTreeResource.
 *
 * The hand-written encoder is there for speed — a JsonResource and a reflection per node
 * were more than a third of the time a large tree took — so nothing but this test notices
 * if the two drift apart.
 */
class TraceTreeResponseTest extends TestCase
{
    public function testANodeIsWrittenExactlyAsTheResourceDescribesIt(): void
    {
        foreach ([$this->node(serviceId: 7, withMetrics: true), $this->node(serviceId: null, withMetrics: false)] as $node) {
            self::assertSame(
                new TraceTreeResource($node)->toArray(),
                TraceTreeResponse::node($node)
            );
        }
    }

    /**
     * Nodes are written in batches, so the joints between batches are where a comma goes
     * missing or doubles up: the body has to be one valid document whatever the count.
     */
    public function testTheBodyIsOneDocumentAcrossBatches(): void
    {
        foreach ([0, 1, 999, 1000, 1001, 2500] as $count) {
            $nodes = [];

            for ($index = 0; $index < $count; $index++) {
                $nodes[] = $this->node(serviceId: 1, withMetrics: true, traceId: "t-$index");
            }

            $decoded = json_decode($this->body($nodes), true, flags: JSON_THROW_ON_ERROR);

            self::assertCount($count, $decoded['data']['items'], "with $count nodes");

            if ($count > 0) {
                self::assertSame('t-' . ($count - 1), $decoded['data']['items'][$count - 1]['trace_id']);
            }
        }
    }

    /**
     * The SConcur bridge streams a response only when it can reach its chunks; one that
     * prints is run to its end into a temporary file first. Going back to echo would still
     * produce the right body — nothing but this test would notice the tree stopped streaming.
     */
    public function testTheBridgeStreamsTheTreeChunkByChunk(): void
    {
        $nodes = [];

        for ($index = 0; $index < 2500; $index++) {
            $nodes[] = $this->node(serviceId: 1, withMetrics: true, traceId: "t-$index");
        }

        $chunksFactory = StreamedChunks::of($this->response($nodes));

        self::assertNotNull($chunksFactory);

        $chunks = [...$chunksFactory()];

        self::assertGreaterThan(3, count($chunks));
        self::assertSame($this->body($nodes), implode('', $chunks));
    }

    public function testATreeStillBeingBuiltHasNoItems(): void
    {
        $response = new TraceTreeResponse(new TraceTreeResultObject(state: $this->state(), items: null));

        $decoded = json_decode($this->send($response), true, flags: JSON_THROW_ON_ERROR);

        self::assertNull($decoded['data']['items']);
    }

    /**
     * @param TraceTreeRawObject[] $nodes
     */
    private function body(array $nodes): string
    {
        return $this->send($this->response($nodes));
    }

    /**
     * What sendContent() prints. Caught by a buffer handler rather than read back with
     * ob_get_clean(): Symfony flushes the buffer after every chunk, and a flush hands the
     * buffer to the handler — the only place the whole body passes through.
     */
    private function send(TraceTreeResponse $response): string
    {
        $body = '';

        ob_start(static function (string $buffer) use (&$body): string {
            $body .= $buffer;

            return '';
        });

        $response->sendContent();

        ob_end_flush();

        return $body;
    }

    /**
     * @param TraceTreeRawObject[] $nodes
     */
    private function response(array $nodes): TraceTreeResponse
    {
        return new TraceTreeResponse(
            new TraceTreeResultObject(
                state: $this->state(),
                items: new TraceTreeRawIterator(
                    transport: static fn(TraceTreeRawObject $node): TraceTreeRawObject => $node,
                    iterator: new ArrayIterator($nodes)
                ),
            )
        );
    }

    private function node(?int $serviceId, bool $withMetrics, string $traceId = 'trace'): TraceTreeRawObject
    {
        return new TraceTreeRawObject(
            serviceId: $serviceId,
            traceId: $traceId,
            parentTraceId: 'parent',
            type: 'http',
            tags: ['a', 'b'],
            status: 'success',
            duration: $withMetrics ? 1.25 : null,
            memory: $withMetrics ? 2.5 : null,
            cpu: $withMetrics ? 3.75 : null,
            loggedAt: Carbon::parse('2026-09-21 10:11:12.345678'),
        );
    }

    private function state(): TraceTreeCacheStateObject
    {
        return new TraceTreeCacheStateObject(
            rootTraceId: 'root',
            version: 'v1',
            status: TraceTreeCacheStateStatusEnum::Finished,
            count: 1,
            error: null,
            startedAt: Carbon::now(),
            finishedAt: Carbon::now(),
            createdAt: Carbon::now(),
            updatedAt: Carbon::now(),
        );
    }
}
