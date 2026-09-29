<?php

namespace Tests\Modules\Mcp\Infrastructure\Http;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\Mcp\Repositories\McpRepository;
use PHPUnit\Framework\MockObject\MockObject;
use SLoggerLaravel\Middleware\HttpMiddleware as SLoggerHttpMiddleware;
use Tests\Modules\Mcp\McpFactoryTrait;
use Tests\TestCase;

class McpAdminHttpTest extends TestCase
{
    use McpFactoryTrait;

    private McpRepository&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(McpRepository::class);

        $this->app->instance(McpRepository::class, $this->repository);
    }

    public function testWithoutSessionIsUnauthorized(): void
    {
        $this->getJson('/admin-api/mcps')->assertUnauthorized();
    }

    public function testListsMcps(): void
    {
        $this->withoutAuth();

        $this->repository->method('find')->willReturn([$this->mcpObject(id: 3, requestsCount: 42)]);

        $this->getJson('/admin-api/mcps')
            ->assertOk()
            ->assertJsonPath('data.0.id', 3)
            ->assertJsonPath('data.0.token', 'token')
            ->assertJsonPath('data.0.enabled', true)
            ->assertJsonPath('data.0.requests_count', 42)
            ->assertJsonPath('data.0.last_used_at', null);
    }

    public function testCreates(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->once())
            ->method('create')
            ->willReturnCallback(fn(string $name, string $token) => $this->mcpObject(token: $token));

        $response = $this->postJson('/admin-api/mcps', ['name' => 'CI agent'])->assertOk();

        $this->assertSame(50, strlen((string) $response->json('data.token')));
    }

    public function testEmptyNameIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->never())->method('create');

        $this->postJson('/admin-api/mcps', ['name' => ''])->assertUnprocessable();
    }

    public function testDisables(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->willReturn($this->mcpObject(id: 3));
        $this->repository->expects($this->once())
            ->method('update')
            ->with(3, 'Claude Code', false);

        $this->patchJson('/admin-api/mcps/3', ['name' => 'Claude Code', 'enabled' => false])->assertOk();
    }

    public function testRegeneratesToken(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->willReturn($this->mcpObject(id: 3));
        $this->repository->expects($this->once())->method('updateToken');

        $this->patchJson('/admin-api/mcps/3/token')->assertOk();
    }

    public function testDeletes(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->once())->method('delete')->with(3)->willReturn(true);

        $this->deleteJson('/admin-api/mcps/3')->assertOk();
    }

    public function testUnknownMcpIsNotFound(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->willReturn(null);
        $this->repository->method('delete')->willReturn(false);

        $this->getJson('/admin-api/mcps/999999')->assertNotFound();
        $this->patchJson('/admin-api/mcps/999999', ['name' => 'x', 'enabled' => true])->assertNotFound();
        $this->patchJson('/admin-api/mcps/999999/token')->assertNotFound();
        $this->deleteJson('/admin-api/mcps/999999')->assertNotFound();
    }

    public function testSettings(): void
    {
        $this->withoutAuth();

        config()->set('mcp.server_name', 'stand');
        config()->set('app.url', 'https://slogger.stand.example.com');

        $this->getJson('/admin-api/mcps/settings')
            ->assertOk()
            ->assertJsonPath('data.server_name', 'stand')
            ->assertJsonPath('data.endpoint_url', 'https://slogger.stand.example.com/mcp');
    }

    private function withoutAuth(): void
    {
        $this->withoutMiddleware([AuthMiddleware::class, SLoggerHttpMiddleware::class]);
    }
}
