<?php

namespace Tests\Modules\Service\Infrastructure\Http;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Repositories\ServiceRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use SLoggerLaravel\Middleware\HttpMiddleware as SLoggerHttpMiddleware;
use Tests\TestCase;

class ServiceAdminHttpTest extends TestCase
{
    private ServiceRepository&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ServiceRepository::class);

        $this->app->instance(ServiceRepository::class, $this->repository);
    }

    public function testWithoutSessionIsUnauthorized(): void
    {
        $this->getJson('/admin-api/services')->assertUnauthorized();
        $this->postJson('/admin-api/services', ['name' => 'billing'])->assertUnauthorized();
        $this->patchJson('/admin-api/services/positions', ['ids' => [1]])->assertUnauthorized();
    }

    public function testListsServicesWithoutTokens(): void
    {
        $this->withoutAuth();

        $this->repository->method('find')->willReturn([
            $this->serviceObject(id: 2, name: 'auth'),
            $this->serviceObject(id: 1, name: 'billing'),
        ]);

        $response = $this->getJson('/admin-api/services')
            ->assertOk()
            ->assertJsonPath('data.0.id', 2)
            ->assertJsonPath('data.1.id', 1);

        $this->assertArrayNotHasKey('api_token', $response->json('data.0'));
    }

    public function testShowsServiceWithToken(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->with(3)->willReturn($this->serviceObject(id: 3, name: 'billing'));

        $this->getJson('/admin-api/services/3')
            ->assertOk()
            ->assertJsonPath('data.id', 3)
            ->assertJsonPath('data.unique_key', 'billing')
            ->assertJsonPath('data.api_token', 'token')
            ->assertJsonPath('data.created_at', '2026-10-22 05:00:00');
    }

    public function testCreates(): void
    {
        $this->withoutAuth();

        $this->repository->method('isExistByUniqueKey')->willReturn(false);
        $this->repository->expects($this->once())
            ->method('create')
            ->with('Billing API', 'billing-api')
            ->willReturn($this->serviceObject(id: 7, name: 'Billing API'));

        $this->postJson('/admin-api/services', ['name' => 'Billing API'])
            ->assertOk()
            ->assertJsonPath('data.id', 7)
            ->assertJsonPath('data.api_token', 'token');
    }

    public function testCreatingDuplicateIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->method('isExistByUniqueKey')->willReturn(true);
        $this->repository->expects($this->never())->method('create');

        $this->postJson('/admin-api/services', ['name' => 'billing'])->assertUnprocessable();
    }

    public function testEmptyNameIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->never())->method('create');
        $this->repository->expects($this->never())->method('update');

        $this->postJson('/admin-api/services', ['name' => ''])->assertUnprocessable();
        $this->patchJson('/admin-api/services/1', ['name' => ''])->assertUnprocessable();
    }

    public function testRenames(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->with(3)->willReturn($this->serviceObject(id: 3, name: 'billing'));
        $this->repository->expects($this->once())
            ->method('isExistByUniqueKey')
            ->with('payments', 3)
            ->willReturn(false);
        $this->repository->expects($this->once())->method('update')->with(3, 'Payments', 'payments');

        $this->patchJson('/admin-api/services/3', ['name' => 'Payments'])->assertOk();
    }

    public function testRenamingToNameOfAnotherServiceIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->willReturn($this->serviceObject(id: 3, name: 'billing'));
        $this->repository->method('isExistByUniqueKey')->willReturn(true);
        $this->repository->expects($this->never())->method('update');

        $this->patchJson('/admin-api/services/3', ['name' => 'auth'])->assertUnprocessable();
    }

    public function testUnknownServiceIsNotFound(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->willReturn(null);

        $this->getJson('/admin-api/services/999999')->assertNotFound();
        $this->patchJson('/admin-api/services/999999', ['name' => 'x'])->assertNotFound();
    }

    public function testSorts(): void
    {
        $this->withoutAuth();

        $this->repository->method('findIds')->willReturn([1, 2, 3]);
        $this->repository->expects($this->once())->method('updatePositions')->with([3, 1, 2]);

        $this->patchJson('/admin-api/services/positions', ['ids' => [3, 1, 2]])->assertOk();
    }

    public function testSortingNotEveryServiceIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->method('findIds')->willReturn([1, 2, 3]);
        $this->repository->expects($this->never())->method('updatePositions');

        $this->patchJson('/admin-api/services/positions', ['ids' => [3, 1]])->assertUnprocessable();
        $this->patchJson('/admin-api/services/positions', ['ids' => [3, 1, 2, 4]])->assertUnprocessable();
        $this->patchJson('/admin-api/services/positions', ['ids' => [3, 1, 1]])->assertUnprocessable();
    }

    private function serviceObject(int $id, string $name): ServiceObject
    {
        return new ServiceObject(
            id: $id,
            name: $name,
            uniqueKey: 'billing',
            apiToken: 'token',
            position: $id,
            createdAt: Carbon::parse('2026-10-22 05:00:00')
        );
    }

    private function withoutAuth(): void
    {
        $this->withoutMiddleware([AuthMiddleware::class, SLoggerHttpMiddleware::class]);
    }
}
