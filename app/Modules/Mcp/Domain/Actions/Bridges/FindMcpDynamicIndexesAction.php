<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexesAction;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexObject;

readonly class FindMcpDynamicIndexesAction
{
    public function __construct(
        private FindTraceDynamicIndexesAction $findTraceDynamicIndexesAction
    ) {
    }

    /**
     * @return TraceDynamicIndexObject[]
     */
    public function handle(): array
    {
        return $this->findTraceDynamicIndexesAction->handle();
    }
}
