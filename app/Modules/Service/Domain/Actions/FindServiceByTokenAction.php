<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\Actions;

use App\Modules\Service\Contracts\Actions\FindServiceByTokenActionInterface;
use App\Modules\Service\Contracts\Repositories\ServiceRepositoryInterface;
use App\Modules\Service\Entities\ServiceObject;

readonly class FindServiceByTokenAction implements FindServiceByTokenActionInterface
{
    public function __construct(private ServiceRepositoryInterface $serviceRepository)
    {
    }

    public function handle(string $token): ?ServiceObject
    {
        return $this->serviceRepository->findByToken($token);
    }
}
