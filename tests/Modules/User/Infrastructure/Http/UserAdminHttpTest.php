<?php

namespace Tests\Modules\User\Infrastructure\Http;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\User\Entities\UserObject;
use App\Modules\User\Parameters\UserCreateParameters;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use SLoggerLaravel\Middleware\HttpMiddleware as SLoggerHttpMiddleware;
use Tests\TestCase;

class UserAdminHttpTest extends TestCase
{
    private UserRepository&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(UserRepository::class);

        $this->app->instance(UserRepository::class, $this->repository);
    }

    public function testWithoutSessionIsUnauthorized(): void
    {
        $this->getJson('/admin-api/users')->assertUnauthorized();
        $this->postJson('/admin-api/users', $this->createPayload())->assertUnauthorized();
        $this->patchJson('/admin-api/users/1', ['first_name' => 'Ann'])->assertUnauthorized();
    }

    public function testListsUsersWithoutPasswords(): void
    {
        $this->withoutAuth();

        $this->repository->method('find')->willReturn([
            $this->userObject(id: 1, firstName: 'Ann'),
            $this->userObject(id: 2, firstName: 'Bob'),
        ]);

        $response = $this->getJson('/admin-api/users')
            ->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonPath('data.1.first_name', 'Bob')
            ->assertJsonPath('data.0.email', 'us****@example.com')
            ->assertJsonPath('data.0.created_at', '2026-10-22 05:00:00')
            ->assertJsonPath('data.0.updated_at', '2026-10-22 06:00:00');

        $this->assertArrayNotHasKey('password', $response->json('data.0'));
    }

    public function testCreates(): void
    {
        $this->withoutAuth();

        $this->repository->method('isExistByEmail')->with('ann@example.com')->willReturn(false);
        $this->repository->expects($this->once())
            ->method('create')
            ->with(
                $this->callback(
                    fn(UserCreateParameters $parameters) => $parameters->firstName === 'Ann'
                        && $parameters->lastName === null
                        && $parameters->email === 'ann@example.com'
                        && $parameters->password === 'secret12'
                )
            )
            ->willReturn(5);
        $this->repository->method('findOneById')->with(5)->willReturn($this->userObject(id: 5, firstName: 'Ann'));

        $this->postJson('/admin-api/users', $this->createPayload())
            ->assertOk()
            ->assertJsonPath('data.id', 5)
            ->assertJsonPath('data.first_name', 'Ann');
    }

    public function testMasksEmails(): void
    {
        $this->withoutAuth();

        $this->repository->method('find')->willReturn([
            $this->userObject(id: 1, firstName: 'Ann', email: 'sppetrovich@gmail.com'),
            $this->userObject(id: 2, firstName: 'Bob', email: 'ab@gmail.com'),
            $this->userObject(id: 3, firstName: 'Kim', email: 'a@gmail.com'),
        ]);

        $this->getJson('/admin-api/users')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'sp****@gmail.com')
            ->assertJsonPath('data.1.email', 'a****@gmail.com')
            ->assertJsonPath('data.2.email', 'a****@gmail.com');
    }

    public function testCreatingWithUsedEmailIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->method('isExistByEmail')->willReturn(true);
        $this->repository->expects($this->never())->method('create');

        $this->postJson('/admin-api/users', $this->createPayload())->assertUnprocessable();
    }

    public function testInvalidCreateIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->never())->method('create');

        $this->postJson('/admin-api/users', [...$this->createPayload(), 'email' => 'wrong'])->assertUnprocessable();
        $this->postJson('/admin-api/users', [...$this->createPayload(), 'password' => 'short'])->assertUnprocessable();
        $this->postJson('/admin-api/users', [...$this->createPayload(), 'first_name' => ''])->assertUnprocessable();
    }

    public function testRenames(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->with(3)->willReturn($this->userObject(id: 3, firstName: 'Ann'));
        $this->repository->expects($this->once())->method('updateName')->with(3, 'Anna', 'Smith');

        $this->patchJson('/admin-api/users/3', ['first_name' => 'Anna', 'last_name' => 'Smith'])
            ->assertOk()
            ->assertJsonPath('data.id', 3);
    }

    public function testRenamingUnknownUserIsNotFound(): void
    {
        $this->withoutAuth();

        $this->repository->method('findOneById')->willReturn(null);
        $this->repository->expects($this->never())->method('updateName');

        $this->patchJson('/admin-api/users/999999', ['first_name' => 'Ann'])->assertNotFound();
    }

    /**
     * @return array<string, string>
     */
    private function createPayload(): array
    {
        return [
            'first_name' => 'Ann',
            'email'      => 'ann@example.com',
            'password'   => 'secret12',
        ];
    }

    private function userObject(int $id, string $firstName, string $email = 'user@example.com'): UserObject
    {
        return new UserObject(
            id: $id,
            firstName: $firstName,
            lastName: null,
            email: $email,
            createdAt: Carbon::parse('2026-10-22 05:00:00'),
            updatedAt: Carbon::parse('2026-10-22 06:00:00')
        );
    }

    private function withoutAuth(): void
    {
        $this->withoutMiddleware([AuthMiddleware::class, SLoggerHttpMiddleware::class]);
    }
}
