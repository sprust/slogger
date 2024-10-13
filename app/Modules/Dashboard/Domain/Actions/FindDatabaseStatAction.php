<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Contracts\Actions\FindDatabaseStatActionInterface;
use App\Modules\Dashboard\Contracts\Repositories\DatabaseStatRepositoryInterface;

readonly class FindDatabaseStatAction implements FindDatabaseStatActionInterface
{
    public function __construct(
        private DatabaseStatRepositoryInterface $databaseStatRepository
    ) {
    }

    public function handle(): array
    {
        return $this->databaseStatRepository->find();
    }
}
