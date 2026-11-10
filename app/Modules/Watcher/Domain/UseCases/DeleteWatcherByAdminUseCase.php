<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Domain\Actions\Mutations\DeleteWatcherAction;

readonly class DeleteWatcherByAdminUseCase
{
    public function __construct(private DeleteWatcherAction $deleteWatcherAction)
    {
    }

    public function handle(int $watcherId): void
    {
        $this->deleteWatcherAction->handle($watcherId);
    }
}
