<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\UseCases;

use App\Modules\Dashboard\Entities\SconcurStatObject;
use App\Modules\Dashboard\Domain\Actions\FindSconcurStatAction;

readonly class FindSconcurStatByAdminUseCase
{
    public function __construct(
        private FindSconcurStatAction $findSconcurStatAction
    ) {
    }

    public function handle(): SconcurStatObject
    {
        return $this->findSconcurStatAction->handle();
    }
}
