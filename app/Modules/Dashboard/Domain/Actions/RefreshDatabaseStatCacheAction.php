<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;

readonly class RefreshDatabaseStatCacheAction
{
    public function __construct(
        private FindDatabaseStatAction $findDatabaseStatAction,
        private DatabaseStatCacheRepository $cacheRepository
    ) {
    }

    public function handle(): void
    {
        $stats = $this->findDatabaseStatAction->handle();

        $this->cacheRepository->put($stats);
    }
}
