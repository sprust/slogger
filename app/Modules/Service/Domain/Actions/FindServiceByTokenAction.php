<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Repositories\ServiceRepository;

readonly class FindServiceByTokenAction
{
    public function __construct(private ServiceRepository $serviceRepository)
    {
    }

    public function handle(string $token): ?ServiceObject
    {
        return $this->serviceRepository->findByToken($token);
    }
}
