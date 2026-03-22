<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Repositories\ServiceRepository;

readonly class FindServicesAction
{
    public function __construct(
        private ServiceRepository $serviceRepository
    ) {
    }

    /**
     * @param int[]|null $ids
     *
     * @return ServiceObject[]
     */
    public function handle(?array $ids = null): array
    {
        return $this->serviceRepository->find(ids: $ids);
    }
}
