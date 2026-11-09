<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceAdminStoreAction;

readonly class DeleteTraceAdminStoreByAdminUseCase
{
    public function __construct(private DeleteTraceAdminStoreAction $deleteTraceAdminStoreAction)
    {
    }

    public function handle(string $id): bool
    {
        return $this->deleteTraceAdminStoreAction->handle(
            id: $id,
        );
    }
}
