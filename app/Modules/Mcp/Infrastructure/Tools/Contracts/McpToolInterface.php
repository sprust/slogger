<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

use App\Services\Clickhouse\ClickhouseQueryException;

interface McpToolInterface
{
    public function name(): string;

    public function title(): string;

    public function description(): string;

    public function schema(): McpToolSchema;

    /**
     * @throws McpToolArgumentsException
     * @throws ClickhouseQueryException the server answers it as a tool error
     */
    public function call(McpToolArguments $arguments): McpToolResult;
}
