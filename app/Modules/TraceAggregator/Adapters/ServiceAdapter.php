<?php

namespace App\Modules\TraceAggregator\Adapters;

use App\Modules\Service\Repository\ServiceRepositoryInterface;
use App\Modules\TraceAggregator\Adapters\Dto\ServiceDto;

readonly class ServiceAdapter
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    )
    {
    }

    /**
     * @return ServiceDto[]
     */
    public function find(): array
    {
        return array_map(
            fn(\App\Modules\Service\Repository\Dto\ServiceDto $dto) => new ServiceDto(
                id: $dto->id,
                name: $dto->name,
            ),
            $this->serviceRepository->find()
        );
    }
}
