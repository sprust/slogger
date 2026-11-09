<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Domain\Actions\Mutations\CloseIncidentAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindClosingUserIdAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use App\Modules\Watcher\Parameters\CloseIncidentByAdminParameters;

readonly class CloseIncidentByAdminUseCase
{
    public function __construct(private CloseIncidentAction $closeIncidentAction, private FindClosingUserIdAction $findClosingUserIdAction)
    {
    }

    /** @throws WatcherIncidentNotFoundException */
    public function handle(CloseIncidentByAdminParameters $parameters): void
    {
        $this->closeIncidentAction->handle(
            incidentId: $parameters->incidentId,
            closedByUserId: $this->findClosingUserIdAction->handle($parameters),
        );
    }
}
