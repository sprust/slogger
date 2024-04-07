<?php

namespace App\Modules\Service\Repositories;

use App\Modules\Service\Domain\Entities\Parameters\ServiceCreateParameters;
use App\Modules\Service\Repositories\Dto\ServiceDto;

interface ServiceRepositoryInterface
{
    /** @return ServiceDto[] */
    public function find(): array;

    public function create(ServiceCreateParameters $parameters): ServiceDto;

    public function findByToken(string $token): ?ServiceDto;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
