<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;

readonly class FindTypedWatcherAction
{
    public function __construct(private FindWatcherAction $findWatcherAction)
    {
    }

    /** @throws WatcherNotFoundException */
    public function handle(int $id, WatcherTypeEnum $type): WatcherObject
    {
        $watcher = $this->findWatcherAction->handle(
            id: $id,
        );

        if ($watcher === null || $watcher->type !== $type) {
            throw new WatcherNotFoundException($id);
        }

        return $watcher;
    }
}
