<?php

namespace Tests\Modules\Mcp\Infrastructure\Protocol;

use App\Modules\Mcp\Infrastructure\Protocol\McpErrorCodeEnum;
use App\Modules\Mcp\Infrastructure\Protocol\McpHeaderValidator;
use App\Modules\Mcp\Infrastructure\Protocol\McpProtocolException;
use App\Modules\Mcp\Infrastructure\Protocol\McpRequestHeaders;
use App\Modules\Mcp\Infrastructure\Protocol\McpRequestMessage;
use App\Modules\Mcp\Infrastructure\Protocol\McpVersionValidator;
use PHPUnit\Framework\TestCase;

class McpHeaderValidatorTest extends TestCase
{
    public function testMatchingHeadersPass(): void
    {
        $version = new McpHeaderValidator()->validate(
            $this->message('tools/call', ['name' => 'get_trace']),
            new McpRequestHeaders('2026-07-28', 'tools/call', 'get_trace')
        );

        $this->assertSame('2026-07-28', $version);
    }

    public function testBase64NameIsDecoded(): void
    {
        $version = new McpHeaderValidator()->validate(
            $this->message('tools/call', ['name' => 'get_trace']),
            new McpRequestHeaders('2026-07-28', 'tools/call', '=?base64?' . base64_encode('get_trace') . '?=')
        );

        $this->assertSame('2026-07-28', $version);
    }

    public function testMissingVersionHeaderNamesTheSupportedVersion(): void
    {
        $exception = $this->failure(
            $this->message('initialize', [], meta: false),
            new McpRequestHeaders(null, null, null)
        );

        $this->assertStringContainsString(McpVersionValidator::SUPPORTED_VERSION, $exception->getMessage());
    }

    public function testVersionMustMatchMeta(): void
    {
        $this->failure(
            $this->message('tools/list', [], meta: false),
            new McpRequestHeaders('2026-07-28', 'tools/list', null)
        );
    }

    public function testMethodMustMatch(): void
    {
        $this->failure(
            $this->message('tools/call', ['name' => 'get_trace']),
            new McpRequestHeaders('2026-07-28', 'tools/list', 'get_trace')
        );
    }

    public function testNameMustMatch(): void
    {
        $this->failure(
            $this->message('tools/call', ['name' => 'get_trace']),
            new McpRequestHeaders('2026-07-28', 'tools/call', 'list_services')
        );
    }

    public function testNameIsRequiredForToolCalls(): void
    {
        $this->failure(
            $this->message('tools/call', ['name' => 'get_trace']),
            new McpRequestHeaders('2026-07-28', 'tools/call', null)
        );
    }

    public function testInvalidCharactersAreRejected(): void
    {
        $this->failure(
            $this->message('tools/list', []),
            new McpRequestHeaders('2026-07-28', "tools/list\n", null)
        );
    }

    public function testUnsupportedVersionListsSupported(): void
    {
        $message = $this->message('tools/list', [], version: '2025-11-25');

        $version = new McpHeaderValidator()->validate($message, new McpRequestHeaders('2025-11-25', 'tools/list', null));

        try {
            new McpVersionValidator()->validate($message, $version);

            $this->fail('No exception');
        } catch (McpProtocolException $exception) {
            $this->assertSame(McpErrorCodeEnum::UnsupportedProtocolVersion, $exception->errorCode);
            $this->assertSame(400, $exception->httpStatus);
            $this->assertSame(['supported' => ['2026-07-28'], 'requested' => '2025-11-25'], $exception->data);
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function message(
        string $method,
        array $params,
        bool $meta = true,
        string $version = '2026-07-28'
    ): McpRequestMessage {
        if ($meta) {
            $params['_meta'] = [McpHeaderValidator::META_PROTOCOL_VERSION => $version];
        }

        return new McpRequestMessage(id: 1, method: $method, params: $params);
    }

    private function failure(McpRequestMessage $message, McpRequestHeaders $headers): McpProtocolException
    {
        try {
            new McpHeaderValidator()->validate($message, $headers);
        } catch (McpProtocolException $exception) {
            $this->assertSame(McpErrorCodeEnum::HeaderMismatch, $exception->errorCode);
            $this->assertSame(400, $exception->httpStatus);

            return $exception;
        }

        $this->fail('No exception');
    }
}
