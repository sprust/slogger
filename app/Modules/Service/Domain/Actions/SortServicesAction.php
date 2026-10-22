<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Exceptions\ServicesSortMismatchException;
use App\Modules\Service\Parameters\ServicesSortParameters;
use App\Modules\Service\Repositories\ServiceRepository;

readonly class SortServicesAction
{
    public function __construct(
        private ServiceRepository $serviceRepository
    ) {
    }

    /**
     * @throws ServicesSortMismatchException
     */
    public function handle(ServicesSortParameters $parameters): void
    {
        $sortedIds = $parameters->ids;
        $actualIds = $this->serviceRepository->findIds();

        sort($sortedIds);
        sort($actualIds);

        if ($sortedIds !== $actualIds) {
            throw new ServicesSortMismatchException();
        }

        $this->serviceRepository->updatePositions($parameters->ids);
    }
}
