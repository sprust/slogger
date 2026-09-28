<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Mutations;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use Illuminate\Support\Str;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class RegenerateMcpTokenAction
{
    public function __construct(
        private McpRepository $mcpRepository,
        private FindMcpAction $findMcpAction
    ) {
    }

    /**
     * @throws McpNotFoundException
     */
    public function handle(int $id): void
    {
        if (is_null($this->findMcpAction->handle($id))) {
            throw new McpNotFoundException($id);
        }

        $this->mcpRepository->updateToken(
            id: $id,
            token: Str::random(50)
        );
    }
}
