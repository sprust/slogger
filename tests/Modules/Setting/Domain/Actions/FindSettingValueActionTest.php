<?php

namespace Tests\Modules\Setting\Domain\Actions;

use App\Modules\Setting\Domain\Actions\FindSettingValueAction;
use App\Modules\Setting\Domain\Services\SettingDefinitionFinder;
use App\Modules\Setting\Domain\Services\SettingValueResolver;
use App\Modules\Setting\Entities\SettingValueObject;
use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Repositories\SettingRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FindSettingValueActionTest extends TestCase
{
    public function testStoredValueWins(): void
    {
        config()->set('cleaner.lifetime_hours', 72);

        $this->assertSame(24, $this->action(stored: 24)->handle(SettingKeyEnum::TracesLifetimeHours));
    }

    public function testDefaultComesFromConfig(): void
    {
        config()->set('module-logs.nginx_keep_days', 14);

        $this->assertSame(14, $this->action(stored: null)->handle(SettingKeyEnum::LogsNginxKeepDays));
    }

    /**
     * @return array<string, array<int>>
     */
    public static function outOfRangeValues(): array
    {
        return [
            'below minimum' => [0],
            'above maximum' => [8761],
        ];
    }

    #[DataProvider('outOfRangeValues')]
    public function testOutOfRangeStoredValueFallsBackToDefault(int $value): void
    {
        config()->set('cleaner.lifetime_hours', 72);

        $this->assertSame(72, $this->action(stored: $value)->handle(SettingKeyEnum::TracesLifetimeHours));
    }

    private function action(?int $stored): FindSettingValueAction
    {
        $repository = $this->createMock(SettingRepository::class);
        $repository->method('findOne')->willReturnCallback(
            static fn(SettingKeyEnum $key): ?SettingValueObject => is_null($stored)
                ? null
                : new SettingValueObject(key: $key, value: $stored, updatedAt: Carbon::now())
        );

        return new FindSettingValueAction($repository, new SettingDefinitionFinder(), new SettingValueResolver());
    }
}
