<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Mutations;

use Illuminate\Support\Str;
use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Parameters\CreateMcpParameters;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class CreateMcpAction
{
    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    public function handle(CreateMcpParameters $parameters): McpObject
    {
        return $this->mcpRepository->create(
            name: $parameters->name,
            token: Str::random(50)
        );
    }
}
