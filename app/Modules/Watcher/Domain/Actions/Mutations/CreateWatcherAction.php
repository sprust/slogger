<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Domain\Services\WatcherFactory;
use App\Modules\Watcher\Domain\Services\WatcherMatchFactory;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Parameters\CreateWatcherParameters;
use App\Modules\Watcher\Repositories\WatcherRepository;
use Illuminate\Support\Carbon;
use LogicException;

readonly class CreateWatcherAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherTypeRegistry $types,
        private WatcherMatchFactory $matchFactory,
        private WatcherFactory $watcherFactory
    ) {
    }

    public function handle(CreateWatcherParameters $parameters): WatcherObject
    {
        $settings = $this->types->for($parameters->type)->makeSettings($parameters->settings);

        $watcher = $this->watcherFactory->make(
            $this->watcherRepository->create(
                name: $parameters->name,
                type: $parameters->type->value,
                enabled: $parameters->enabled,
                cooldownSeconds: $parameters->cooldownSeconds,
                settings: $settings->toArray(),
                traceMatch: $this->matchFactory->toArray($this->matchFactory->make($settings)),
                // Its line starts now. Until it is as long as the window a check needs, the
                // checks that report an absence stay quiet — an empty window nobody was
                // counting for is not silence.
                collectSince: Carbon::now()
            )
        );

        // The type came from the enum a moment ago, so the factory cannot fail to read it
        // back. Stated rather than assumed: the factory answers null for a row it cannot
        // make sense of, and silently returning one here would be worse than saying so.
        if (is_null($watcher)) {
            throw new LogicException("Watcher of type [{$parameters->type->value}] could not be read back");
        }

        return $watcher;
    }
}
