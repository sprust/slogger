<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Controllers;

use App\Modules\Dashboard\Domain\Actions\FindSconcurStatAction;
use App\Modules\Dashboard\Infrastructure\Http\Resources\SconcurStatResource;

readonly class SconcurStatController
{
    public function __construct(
        private FindSconcurStatAction $findSconcurStatAction
    ) {
    }

    public function index(): SconcurStatResource
    {
        return new SconcurStatResource(
            $this->findSconcurStatAction->handle()
        );
    }
}
