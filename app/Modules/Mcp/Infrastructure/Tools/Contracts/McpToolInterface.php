<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

interface McpToolInterface
{
    public function name(): string;

    public function title(): string;

    public function description(): string;

    public function schema(): McpToolSchema;

    /**
     * @throws McpToolArgumentsException
     */
    public function call(McpToolArguments $arguments): McpToolResult;
}
