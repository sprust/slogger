<?php

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Entities\Objects\ServiceStatObject;
use App\Modules\Dashboard\Domain\Entities\Transports\DatabaseStatTransport;
use App\Modules\Dashboard\Repositories\Dto\DatabaseStatDto;
use App\Modules\Dashboard\Repositories\Interfaces\DatabaseStatRepositoryInterface;

readonly class FindDatabaseStatAction
{
    public function __construct(
        private DatabaseStatRepositoryInterface $databaseStatRepository
    ) {
    }

    /**
     * @return ServiceStatObject[]
     */
    public function handle(): array
    {
        return array_map(
            fn(DatabaseStatDto $dto) => DatabaseStatTransport::toObject($dto),
            $this->databaseStatRepository->find()
        );
    }
}
