<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Entities\DatabaseStatObject;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;

readonly class FindDatabaseStatAction
{
    public function __construct(
        private DatabaseStatRepository $databaseStatRepository
    ) {
    }

    /**
     * @return DatabaseStatObject[]
     */
    public function handle(): array
    {
        return $this->databaseStatRepository->find();
    }
}
