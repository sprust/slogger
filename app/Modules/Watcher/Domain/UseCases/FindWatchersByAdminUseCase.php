<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatchersAction;

readonly class FindWatchersByAdminUseCase
{
    public function __construct(private FindWatchersAction $findWatchersAction)
    {
    }

    /**
     * @return WatcherObject[]
     */
    public function handle(?bool $enabled = null): array
    {
        return $this->findWatchersAction->handle(
            enabled: $enabled,
        );
    }
}
