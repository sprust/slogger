<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Entities\WatcherTypeObject;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherTypesAction;

readonly class FindWatcherTypesByAdminUseCase
{
    public function __construct(
        private FindWatcherTypesAction $findWatcherTypesAction
    ) {
    }

    /**
     * @return WatcherTypeObject[]
     */
    public function handle(): array
    {
        return $this->findWatcherTypesAction->handle();
    }
}
