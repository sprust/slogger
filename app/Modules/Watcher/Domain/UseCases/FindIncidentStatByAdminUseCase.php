<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Entities\WatcherIncidentStatObject;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentStatAction;

readonly class FindIncidentStatByAdminUseCase
{
    public function __construct(
        private FindIncidentStatAction $findIncidentStatAction
    ) {
    }

    public function handle(): WatcherIncidentStatObject
    {
        return $this->findIncidentStatAction->handle();
    }
}
