<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\Mcp\Repositories\McpRepository;

readonly class McpFactory
{
    public function __construct(private McpRepository $mcpRepository)
    {
    }

    public function create(?string $name = null, ?string $token = null): int
    {
        return $this->mcpRepository->create(
            name: $name ?? fake()->word(),
            token: $token ?? fake()->uuid(),
        )->id;
    }
}
