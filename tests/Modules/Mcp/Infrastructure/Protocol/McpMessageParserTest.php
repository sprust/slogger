<?php

namespace Tests\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Infrastructure\Protocol\McpErrorCodeEnum;
use App\Modules\Mcp\Infrastructure\Protocol\McpMessageParser;
use App\Modules\Mcp\Infrastructure\Protocol\McpNotificationMessage;
use App\Modules\Mcp\Infrastructure\Protocol\McpProtocolException;
use App\Modules\Mcp\Infrastructure\Protocol\McpRequestMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class McpMessageParserTest extends TestCase
{
    public function testParsesARequest(): void
    {
        $message = new McpMessageParser()->parse('{"jsonrpc":"2.0","id":7,"method":"tools/list","params":{"a":1}}');

        $this->assertInstanceOf(McpRequestMessage::class, $message);
        $this->assertSame(7, $message->id);
        $this->assertSame('tools/list', $message->method);
        $this->assertSame(['a' => 1], $message->params);
    }

    public function testParsesANotification(): void
    {
        $message = new McpMessageParser()->parse('{"jsonrpc":"2.0","method":"notifications/initialized"}');

        $this->assertInstanceOf(McpNotificationMessage::class, $message);
    }

    /**
     * @return array<string, array{string, McpErrorCodeEnum}>
     */
    public static function invalidBodies(): array
    {
        return [
            'not json'       => ['{"jsonrpc":', McpErrorCodeEnum::ParseError],
            'batch'          => ['[{"jsonrpc":"2.0","id":1,"method":"tools/list"}]', McpErrorCodeEnum::InvalidRequest],
            'scalar'         => ['42', McpErrorCodeEnum::InvalidRequest],
            'no jsonrpc'     => ['{"id":1,"method":"tools/list"}', McpErrorCodeEnum::InvalidRequest],
            'no method'      => ['{"jsonrpc":"2.0","id":1}', McpErrorCodeEnum::InvalidRequest],
            'list params'    => ['{"jsonrpc":"2.0","id":1,"method":"tools/list","params":[1]}', McpErrorCodeEnum::InvalidRequest],
            'object id'      => ['{"jsonrpc":"2.0","id":{},"method":"tools/list"}', McpErrorCodeEnum::InvalidRequest],
        ];
    }

    #[DataProvider('invalidBodies')]
    public function testRejectsInvalidBodies(string $body, McpErrorCodeEnum $code): void
    {
        try {
            new McpMessageParser()->parse($body);

            $this->fail('No exception');
        } catch (McpProtocolException $exception) {
            $this->assertSame($code, $exception->errorCode);
            $this->assertSame(400, $exception->httpStatus);
        }
    }
}
