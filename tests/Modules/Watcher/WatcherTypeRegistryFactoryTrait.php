<?php

namespace Tests\Modules\Watcher;

use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\ManyTracesChecker;
use App\Modules\Watcher\Domain\Services\Events\BufferOverflowEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\InvalidBufferGrownEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\NoNewTracesEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\SlowTracesEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\ManyTracesEventPayloadMapper;
use App\Modules\Watcher\Domain\Services\Events\WatcherEventGroupMapper;
use App\Modules\Watcher\Domain\Services\Types\BufferOverflowWatcherType;
use App\Modules\Watcher\Domain\Services\Types\InvalidBufferGrownWatcherType;
use App\Modules\Watcher\Domain\Services\Types\NoNewTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\SlowTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\ManyTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use Psr\Log\NullLogger;

/**
 * The registry as the container builds it, with the checkers stubbed and the payload
 * mappers real: a mapper has no collaborator worth faking, and a test that stubs one is
 * testing nothing.
 */
trait WatcherTypeRegistryFactoryTrait
{
    private function watcherTypeRegistry(
        ?BufferOverflowChecker $bufferOverflow = null,
        ?InvalidBufferGrownChecker $invalidBufferGrown = null,
        ?NoNewTracesChecker $noNewTraces = null,
        ?ManyTracesChecker $manyTraces = null,
        ?SlowTracesChecker $slowTraces = null
    ): WatcherTypeRegistry {
        $groups = new WatcherEventGroupMapper(new NullLogger());

        return new WatcherTypeRegistry(
            new BufferOverflowWatcherType(
                $bufferOverflow ?? $this->createMock(BufferOverflowChecker::class),
                new BufferOverflowEventPayloadMapper()
            ),
            new InvalidBufferGrownWatcherType(
                $invalidBufferGrown ?? $this->createMock(InvalidBufferGrownChecker::class),
                new InvalidBufferGrownEventPayloadMapper()
            ),
            new NoNewTracesWatcherType(
                $noNewTraces ?? $this->createMock(NoNewTracesChecker::class),
                new NoNewTracesEventPayloadMapper()
            ),
            new ManyTracesWatcherType(
                $manyTraces ?? $this->createMock(ManyTracesChecker::class),
                new ManyTracesEventPayloadMapper($groups)
            ),
            new SlowTracesWatcherType(
                $slowTraces ?? $this->createMock(SlowTracesChecker::class),
                new SlowTracesEventPayloadMapper($groups)
            )
        );
    }
}
