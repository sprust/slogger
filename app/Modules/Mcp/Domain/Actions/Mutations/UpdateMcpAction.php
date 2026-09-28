<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Mutations;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Parameters\UpdateMcpParameters;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class UpdateMcpAction
{
    public function __construct(
        private McpRepository $mcpRepository,
        private FindMcpAction $findMcpAction
    ) {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(UpdateMcpParameters $parameters): void
    {
        if (is_null($this->findMcpAction->handle($parameters->id))) {
            throw new McpNotFoundException($parameters->id);
        }

        $this->mcpRepository->update(
            id: $parameters->id,
            name: $parameters->name,
            enabled: $parameters->enabled
        );
    }
}
