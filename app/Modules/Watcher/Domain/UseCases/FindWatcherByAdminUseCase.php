<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Domain\Actions\Queries\FindTypedWatcherAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class FindWatcherByAdminUseCase
{
    public function __construct(private FindTypedWatcherAction $findTypedWatcherAction)
    {
    }

    /** @throws WatcherNotFoundException */
    public function handle(int $id, WatcherTypeEnum $type): WatcherObject
    {
        return $this->findTypedWatcherAction->handle(
            id: $id,
            type: $type,
        );
    }
}
