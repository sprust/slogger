<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Service\Entities\ServiceObject;

readonly class GetServicesTool implements McpToolInterface
{
    public function __construct(
        private FindMcpServicesAction $findMcpServicesAction
    ) {
    }

    public function name(): string
    {
        return 'get_services';
    }

    public function title(): string
    {
        return 'List services';
    }

    public function description(): string
    {
        return 'Services that send traces to this SLogger installation: id and name. '
            . 'Use it to resolve the service the user means. No index needed.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'query',
                type: McpToolPropertyTypeEnum::String,
                description: 'Case-insensitive part of the service name.',
                max: 255
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        return new McpToolResult(
            data: [
                'services' => array_map(
                    static fn(ServiceObject $service) => [
                        'id'   => $service->id,
                        'name' => $service->name,
                    ],
                    $this->findMcpServicesAction->handle($arguments->stringNull('query'))
                ),
            ]
        );
    }
}
