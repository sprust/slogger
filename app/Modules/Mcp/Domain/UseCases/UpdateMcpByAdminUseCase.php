<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\UseCases;

use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Parameters\UpdateMcpParameters;
use App\Modules\Mcp\Domain\Actions\Mutations\UpdateMcpAction;

readonly class UpdateMcpByAdminUseCase
{
    public function __construct(private UpdateMcpAction $updateMcpAction)
    {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(UpdateMcpParameters $parameters): void
    {
        $this->updateMcpAction->handle($parameters);
    }
}
