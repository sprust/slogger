<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Repositories\ServiceRepository;

readonly class FindServiceAction
{
    public function __construct(
        private ServiceRepository $serviceRepository
    ) {
    }

    public function handle(int $id): ?ServiceObject
    {
        return $this->serviceRepository->findOneById($id);
    }
}
