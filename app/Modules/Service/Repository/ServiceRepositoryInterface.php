<?php

namespace App\Modules\Service\Repository;

use App\Modules\Service\Repository\Dto\ServiceDto;
use App\Modules\Service\Services\Parameters\ServiceCreateParameters;

interface ServiceRepositoryInterface
{
    /** @return ServiceDto[] */
    public function find(): array;

    public function create(ServiceCreateParameters $parameters): ServiceDto;

    public function findByToken(string $token): ?ServiceDto;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
