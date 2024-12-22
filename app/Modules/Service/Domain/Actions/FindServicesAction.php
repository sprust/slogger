<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Contracts\Actions\FindServicesActionInterface;
use App\Modules\Service\Contracts\Repositories\ServiceRepositoryInterface;

readonly class FindServicesAction implements FindServicesActionInterface
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function handle(?array $ids = null): array
    {
        return $this->serviceRepository->find(ids: $ids);
    }
}
