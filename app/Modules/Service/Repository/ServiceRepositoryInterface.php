<?php

namespace App\Modules\Service\Repository;

use App\Models\Services\Service;
use App\Modules\Service\Dto\Objects\ServiceDetailObject;
use App\Modules\Service\Dto\Objects\ServiceObject;
use App\Modules\Service\Dto\Parameters\ServiceCreateParameters;

interface ServiceRepositoryInterface
{
    /** @return ServiceObject[] */
    public function find(): array;

    public function findById(int $id): ?ServiceDetailObject;

    public function create(ServiceCreateParameters $parameters): Service;

    public function findByToken(string $token): ?Service;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
