<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\UseCases;

use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentsAction;

readonly class FindIncidentsByAdminUseCase
{
    public function __construct(private FindIncidentsAction $findIncidentsAction)
    {
    }

    /**
     * @return WatcherIncidentObject[]
     */
    public function handle(FindIncidentsParameters $parameters): array
    {
        return $this->findIncidentsAction->handle($parameters);
    }
}
