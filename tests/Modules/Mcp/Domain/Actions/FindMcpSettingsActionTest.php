<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use App\Modules\Mcp\Domain\Exceptions\McpServerNameInvalidException;
use Tests\TestCase;

class FindMcpSettingsActionTest extends TestCase
{
    public function testServerNameDefaultsToAppEnv(): void
    {
        $this->assertSame(env('APP_ENV'), config('mcp.server_name'));
    }

    public function testBuildsEndpointFromAppUrl(): void
    {
        config()->set('mcp.server_name', 'prod');
        config()->set('app.url', 'https://slogger.example.com/');

        $settings = new FindMcpSettingsAction()->handle();

        $this->assertSame('prod', $settings->serverName);
        $this->assertSame('https://slogger.example.com/mcp', $settings->endpointUrl);
    }

    public function testInvalidServerNameThrows(): void
    {
        config()->set('mcp.server_name', 'Prod Server');

        $this->expectException(McpServerNameInvalidException::class);

        new FindMcpSettingsAction()->handle();
    }
}
