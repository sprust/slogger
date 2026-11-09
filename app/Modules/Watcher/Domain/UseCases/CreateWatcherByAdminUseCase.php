<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Parameters\CreateWatcherParameters;
use App\Modules\Watcher\Domain\Actions\Mutations\CreateWatcherAction;

readonly class CreateWatcherByAdminUseCase
{
    public function __construct(private CreateWatcherAction $createWatcherAction)
    {
    }

    public function handle(CreateWatcherParameters $parameters): WatcherObject
    {
        return $this->createWatcherAction->handle(
            parameters: $parameters,
        );
    }
}
