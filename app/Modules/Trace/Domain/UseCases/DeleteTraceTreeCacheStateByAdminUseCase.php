<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreeCacheStateAction;

readonly class DeleteTraceTreeCacheStateByAdminUseCase
{
    public function __construct(
        private DeleteTraceTreeCacheStateAction $deleteTraceTreeCacheStateAction
    ) {
    }

    public function handle(string $rootTraceId): void
    {
        $this->deleteTraceTreeCacheStateAction->handle($rootTraceId);
    }
}
