<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\UpdateWatcherAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Parameters\UpdateWatcherParameters;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class UpdateWatcherByAdminUseCase
{
    public function __construct(
        private FindTypedWatcherAction $findTypedWatcherAction,
        private UpdateWatcherAction $updateWatcherAction
    ) {
    }

    /** @throws WatcherNotFoundException */
    public function handle(UpdateWatcherParameters $parameters, WatcherTypeEnum $type): void
    {
        $this->findTypedWatcherAction->handle(
            id: $parameters->id,
            type: $type,
        );

        $this->updateWatcherAction->handle($parameters);
    }
}
