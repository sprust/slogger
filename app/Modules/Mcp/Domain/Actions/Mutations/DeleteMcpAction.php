<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Mutations;

use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class DeleteMcpAction
{
    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(int $id): void
    {
        if (!$this->mcpRepository->delete($id)) {
            throw new McpNotFoundException($id);
        }
    }
}
