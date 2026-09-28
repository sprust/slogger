<?php

namespace Tests\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchemaCompiler;
use PHPUnit\Framework\TestCase;

class McpToolSchemaCompilerTest extends TestCase
{
    public function testCompilesJsonSchema(): void
    {
        $schema = json_decode(
            (string) json_encode(new McpToolSchemaCompiler()->toJsonSchema($this->schema())),
            true
        );

        $this->assertSame(
            [
                'type'                 => 'object',
                'properties'           => [
                    'trace_id' => ['type' => 'string', 'description' => 'Trace id', 'minLength' => 1, 'maxLength' => 100],
                    'status'   => ['type' => 'string', 'description' => 'Status', 'enum' => ['opened', 'closed'], 'minLength' => 1],
                    'page'     => ['type' => 'integer', 'description' => 'Page', 'minimum' => 1],
                    'types'    => ['type' => 'array', 'description' => 'Types', 'items' => ['type' => 'string'], 'maxItems' => 5],
                ],
                'additionalProperties' => false,
                'required'             => ['trace_id'],
            ],
            $schema
        );
    }

    public function testEmptySchemaHasObjectProperties(): void
    {
        $this->assertSame(
            '{"type":"object","properties":{},"additionalProperties":false}',
            json_encode(new McpToolSchemaCompiler()->toJsonSchema(new McpToolSchema()))
        );
    }

    public function testCompilesRules(): void
    {
        $this->assertSame(
            [
                'trace_id' => ['required', 'string', 'min:1', 'max:100'],
                'status'   => ['sometimes', 'string', 'min:1', 'in:opened,closed'],
                'page'     => ['sometimes', 'integer', 'min:1'],
                'types'    => ['sometimes', 'array', 'list', 'max:5'],
                'types.*'  => ['required', 'string', 'min:1'],
            ],
            new McpToolSchemaCompiler()->toRules($this->schema())
        );
    }

    private function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty('trace_id', McpToolPropertyTypeEnum::String, 'Trace id', required: true, min: 1, max: 100),
            new McpToolProperty('status', McpToolPropertyTypeEnum::String, 'Status', enum: ['opened', 'closed']),
            new McpToolProperty('page', McpToolPropertyTypeEnum::Integer, 'Page', min: 1),
            new McpToolProperty('types', McpToolPropertyTypeEnum::StringList, 'Types', max: 5),
        ]);
    }
}
