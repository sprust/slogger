<?php

declare(strict_types=1);

namespace App\Modules\Notification\Domain\UseCases;

use App\Modules\Notification\Parameters\IncidentSnapshotParameters;
use App\Modules\Notification\Domain\Actions\Mutations\EnqueueIncidentNotificationsAction;

readonly class EnqueueIncidentNotificationsUseCase
{
    public function __construct(private EnqueueIncidentNotificationsAction $enqueueIncidentNotificationsAction)
    {
    }

    public function handle(IncidentSnapshotParameters $parameters): void
    {
        $this->enqueueIncidentNotificationsAction->handle($parameters);
    }
}
