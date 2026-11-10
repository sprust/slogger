<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Watcher\Parameters\CloseIncidentByAdminParameters;

readonly class FindClosingUserIdAction
{
    public function __construct(
        private FindUserByTokenAction $findUserByTokenAction
    ) {
    }

    public function handle(CloseIncidentByAdminParameters $parameters): ?int
    {
        return $parameters->token
            ? $this->findUserByTokenAction->handle($parameters->token)?->id
            : null;
    }
}
