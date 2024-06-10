<?php

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Actions\Interfaces\FindServicesActionInterface;
use App\Modules\Service\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Domain\Entities\Transports\ServiceTransport;
use App\Modules\Service\Repositories\Dto\ServiceDto;
use App\Modules\Service\Repositories\ServiceRepositoryInterface;

readonly class FindServicesAction implements FindServicesActionInterface
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {
    }

    /**
     * @return ServiceObject[]
     */
    public function handle(): array
    {
        return array_map(
            fn(ServiceDto $dto) => ServiceTransport::toObject($dto),
            $this->serviceRepository->find()
        );
    }
}
