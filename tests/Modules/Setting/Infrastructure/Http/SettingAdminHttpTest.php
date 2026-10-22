<?php

namespace Tests\Modules\Setting\Infrastructure\Http;

use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\Setting\Entities\SettingValueObject;
use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Repositories\SettingRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use SLoggerLaravel\Middleware\HttpMiddleware as SLoggerHttpMiddleware;
use Tests\TestCase;

class SettingAdminHttpTest extends TestCase
{
    private SettingRepository&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(SettingRepository::class);

        $this->app->instance(SettingRepository::class, $this->repository);

        config()->set('cleaner.lifetime_hours', 72);
        config()->set('module-logs.nginx_keep_days', 14);
    }

    public function testWithoutSessionIsUnauthorized(): void
    {
        $this->getJson('/admin-api/settings')->assertUnauthorized();
        $this->patchJson('/admin-api/settings/traces_lifetime_hours', ['value' => 24])->assertUnauthorized();
        $this->deleteJson('/admin-api/settings/traces_lifetime_hours')->assertUnauthorized();
    }

    public function testListsStoredValuesAndDefaults(): void
    {
        $this->withoutAuth();

        $this->repository->method('find')->willReturn([
            new SettingValueObject(
                key: SettingKeyEnum::TracesLifetimeHours,
                value: 24,
                updatedAt: Carbon::parse('2026-10-22 05:00:00')
            ),
        ]);

        $this->getJson('/admin-api/settings')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'traces_lifetime_hours')
            ->assertJsonPath('data.0.env_name', 'TRACES_LIFETIME_HOURS')
            ->assertJsonPath('data.0.value', 24)
            ->assertJsonPath('data.0.default_value', 72)
            ->assertJsonPath('data.0.is_overridden', true)
            ->assertJsonPath('data.0.updated_at', '2026-10-22 05:00:00')
            ->assertJsonPath('data.1.key', 'logs_nginx_keep_days')
            ->assertJsonPath('data.1.value', 14)
            ->assertJsonPath('data.1.default_value', 14)
            ->assertJsonPath('data.1.is_overridden', false)
            ->assertJsonPath('data.1.updated_at', null);
    }

    public function testInvalidStoredValueFallsBackToDefault(): void
    {
        $this->withoutAuth();

        $this->repository->method('find')->willReturn([
            new SettingValueObject(key: SettingKeyEnum::TracesLifetimeHours, value: 0, updatedAt: Carbon::now()),
            new SettingValueObject(key: SettingKeyEnum::LogsNginxKeepDays, value: 3651, updatedAt: Carbon::now()),
        ]);

        $this->getJson('/admin-api/settings')
            ->assertOk()
            ->assertJsonPath('data.0.value', 72)
            ->assertJsonPath('data.0.is_overridden', false)
            ->assertJsonPath('data.1.value', 14)
            ->assertJsonPath('data.1.is_overridden', false);
    }

    public function testUpdates(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->once())->method('set')->with(SettingKeyEnum::LogsNginxKeepDays, 30);

        $this->patchJson('/admin-api/settings/logs_nginx_keep_days', ['value' => 30])->assertOk();
    }

    public function testValueOutOfRangeIsRejected(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->never())->method('set');

        $this->patchJson('/admin-api/settings/traces_lifetime_hours', ['value' => 0])->assertUnprocessable();
        $this->patchJson('/admin-api/settings/traces_lifetime_hours', ['value' => 8761])->assertUnprocessable();
        $this->patchJson('/admin-api/settings/traces_lifetime_hours', ['value' => 'many'])->assertUnprocessable();
    }

    public function testUnknownKeyIsNotFound(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->never())->method('set');
        $this->repository->expects($this->never())->method('delete');

        $this->patchJson('/admin-api/settings/app_key', ['value' => 1])->assertNotFound();
        $this->deleteJson('/admin-api/settings/app_key')->assertNotFound();
    }

    public function testResets(): void
    {
        $this->withoutAuth();

        $this->repository->expects($this->once())->method('delete')->with(SettingKeyEnum::TracesLifetimeHours);

        $this->deleteJson('/admin-api/settings/traces_lifetime_hours')->assertOk();
    }

    private function withoutAuth(): void
    {
        $this->withoutMiddleware([AuthMiddleware::class, SLoggerHttpMiddleware::class]);
    }
}
