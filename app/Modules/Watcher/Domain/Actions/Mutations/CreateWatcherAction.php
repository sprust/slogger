<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Parameters\CreateWatcherParameters;
use App\Modules\Watcher\Repositories\Services\WatcherMatchFactory;
use App\Modules\Watcher\Repositories\WatcherRepository;
use Illuminate\Support\Carbon;

readonly class CreateWatcherAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherMatchFactory $matchFactory
    ) {
    }

    public function handle(CreateWatcherParameters $parameters): WatcherObject
    {
        return $this->watcherRepository->create(
            name: $parameters->name,
            type: $parameters->type,
            enabled: $parameters->enabled,
            cooldownSeconds: $parameters->cooldownSeconds,
            settings: $parameters->settings,
            match: $this->matchFactory->make($parameters->settings),
            // Its line starts now. Until it is as long as the window a check needs, the
            // checks that report an absence stay quiet — an empty window nobody was
            // counting for is not silence.
            collectSince: Carbon::now()
        );
    }
}
