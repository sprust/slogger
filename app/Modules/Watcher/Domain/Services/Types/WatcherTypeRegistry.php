<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

/**
 * The one place a watcher type turns into behaviour.
 *
 * There used to be three: one deciding how to read the settings, one describing the type
 * to the panel, one picking the checker — and two of them in the repository layer, which
 * has no business knowing what a type means. Everything a type is now hangs off its
 * definition, and this is the only `match` over the enum in the module.
 */
readonly class WatcherTypeRegistry
{
    public function __construct(
        private BufferOverflowWatcherType $bufferOverflow,
        private InvalidBufferGrownWatcherType $invalidBufferGrown,
        private NoNewTracesWatcherType $noNewTraces,
        private TracesSpikeWatcherType $tracesSpike,
        private SlowTracesWatcherType $slowTraces
    ) {
    }

    /**
     * A `match` rather than a map built at boot: a case added without a definition then
     * fails to compile instead of at the first watcher of that type.
     */
    public function for(WatcherTypeEnum $type): WatcherTypeDefinitionInterface
    {
        return match ($type) {
            WatcherTypeEnum::BufferOverflow     => $this->bufferOverflow,
            WatcherTypeEnum::InvalidBufferGrown => $this->invalidBufferGrown,
            WatcherTypeEnum::NoNewTraces        => $this->noNewTraces,
            WatcherTypeEnum::TracesSpike        => $this->tracesSpike,
            WatcherTypeEnum::SlowTraces         => $this->slowTraces,
        };
    }

    /**
     * @return WatcherTypeObject[]
     */
    public function describeAll(): array
    {
        return array_map(
            fn(WatcherTypeEnum $type): WatcherTypeObject => $this->for($type)->describe(),
            WatcherTypeEnum::cases()
        );
    }
}
