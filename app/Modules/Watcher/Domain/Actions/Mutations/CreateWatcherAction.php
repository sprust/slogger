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

        return $this->watcherFactory->make(
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
    }
}
