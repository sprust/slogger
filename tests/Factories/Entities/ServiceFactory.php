<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\Service\Repositories\ServiceRepository;

readonly class ServiceFactory
{
    public function __construct(private ServiceRepository $serviceRepository)
    {
    }

    public function create(?string $name = null, ?string $uniqueKey = null): int
    {
        return $this->serviceRepository->create(
            name: $name ?? fake()->word(),
            uniqueKey: $uniqueKey ?? fake()->uuid(),
        )->id;
    }
}
