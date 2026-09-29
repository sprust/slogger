<?php

namespace Tests\Modules\Mcp\Infrastructure\Http;

use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;

readonly class FakeMcpTool implements McpToolInterface
{
    public function __construct(
        private string $name
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function title(): string
    {
        return 'Fake';
    }

    public function description(): string
    {
        return 'Fake tool, no index needed.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty('thing_id', McpToolPropertyTypeEnum::String, 'Thing id', required: true),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        if ($arguments->string('thing_id') === 'missing') {
            return new McpToolResult(
                data: ['error' => 'not_found', 'hint' => 'Thing not found.'],
                isError: true
            );
        }

        return new McpToolResult(
            data: ['thing_id' => $arguments->string('thing_id'), 'long' => str_repeat('x', 600)]
        );
    }
}
