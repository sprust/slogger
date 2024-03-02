<?php

namespace App\Modules\Service\Repository;

use App\Models\Services\Service;
use App\Modules\Service\Dto\Parameters\ServiceCreateParameters;

interface ServiceRepositoryInterface
{
    public function create(ServiceCreateParameters $parameters): Service;

    public function findByToken(string $token): ?Service;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
