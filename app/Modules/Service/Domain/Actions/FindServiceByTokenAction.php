<?php

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Domain\Actions\Interfaces\FindServiceByTokenActionInterface;
use App\Modules\Service\Domain\Entities\Objects\ServiceObject;
use App\Modules\Service\Domain\Entities\Transports\ServiceTransport;
use App\Modules\Service\Repositories\Dto\ServiceDto;
use App\Modules\Service\Repositories\ServiceRepositoryInterface;

readonly class FindServiceByTokenAction implements FindServiceByTokenActionInterface
{
    public function __construct(private ServiceRepositoryInterface $serviceRepository)
    {
    }

    public function handle(string $token): ?ServiceObject
    {
        /** @var ServiceDto|null $serviceDto */
        $serviceDto = $this->serviceRepository->findByToken($token);

        if (!$serviceDto) {
            return null;
        }

        return ServiceTransport::toObject($serviceDto);
    }
}
