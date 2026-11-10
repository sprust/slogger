<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Parameters\CreateMcpParameters;
use App\Modules\Mcp\Domain\Actions\Mutations\CreateMcpAction;

readonly class CreateMcpByAdminUseCase
{
    public function __construct(private CreateMcpAction $createMcpAction)
    {
    }

    public function handle(CreateMcpParameters $parameters): McpObject
    {
        return $this->createMcpAction->handle($parameters);
    }
}
