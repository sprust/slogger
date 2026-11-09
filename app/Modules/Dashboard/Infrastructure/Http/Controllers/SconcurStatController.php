<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Controllers;

use App\Modules\Dashboard\Domain\UseCases\FindSconcurStatByAdminUseCase;
use App\Modules\Dashboard\Infrastructure\Http\Resources\SconcurStatResource;

readonly class SconcurStatController
{
    public function __construct(private FindSconcurStatByAdminUseCase $findSconcurStatByAdminUseCase)
    {
    }

    public function index(): SconcurStatResource
    {
        return new SconcurStatResource($this->findSconcurStatByAdminUseCase->handle());
    }
}
