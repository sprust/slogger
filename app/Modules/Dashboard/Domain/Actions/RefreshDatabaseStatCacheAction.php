<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Repositories\DatabaseStatCacheRepository;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;

readonly class RefreshDatabaseStatCacheAction
{
    public function __construct(
        private DatabaseStatRepository $statRepository,
        private DatabaseStatCacheRepository $cacheRepository,
    ) {
    }

    public function handle(): void
    {
        $stats = $this->statRepository->find();

        $this->cacheRepository->put($stats);
    }
}
