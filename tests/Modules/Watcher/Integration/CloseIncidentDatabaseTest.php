<?php

declare(strict_types=1);

namespace Tests\Modules\Watcher\Integration;

use Tests\Modules\BaseModuleTestCase;
use Illuminate\Support\Facades\Event;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Modules\Watcher\Domain\UseCases\CloseIncidentByAdminUseCase;
use App\Modules\Watcher\Parameters\CloseIncidentByAdminParameters;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use Tests\Factories\Entities\IncidentFactory;
use Tests\Factories\Entities\UserFactory;
use Tests\Factories\Entities\UserTokenFactory;

class CloseIncidentDatabaseTest extends BaseModuleTestCase
{
    #[DataProvider('dataProvider')]
    public function test(
        bool $closeTwice
    ): void {
        $userTokenFactory = $this->appMake(UserTokenFactory::class);
        $incidentId       = $this->appMake(IncidentFactory::class)->create();
        $userId           = $this->appMake(UserFactory::class)->create();
        $token            = $userTokenFactory->create(
            userId: $userId,
        );
        $closeIncidentByAdminUseCase = $this->appMake(CloseIncidentByAdminUseCase::class);

        $closeIncidentByAdminUseCase->handle(
            new CloseIncidentByAdminParameters(
                incidentId: $incidentId,
                token: $token,
            )
        );

        if ($closeTwice) {
            $closeIncidentByAdminUseCase->handle(
                new CloseIncidentByAdminParameters(
                    incidentId: $incidentId,
                    token: $userTokenFactory->create(),
                )
            );
        }

        Event::assertDispatchedTimes(
            event: WatcherIncidentChangedEvent::class,
            times: 1
        );

        $incident = $this->appMake(WatcherIncidentRepository::class)->findById($incidentId);

        self::assertNotNull(
            $incident
        );
        self::assertSame(
            expected: WatcherIncidentStatusEnum::Closed,
            actual: $incident->status
        );
        self::assertSame(
            expected: $userId,
            actual: $incident->closedByUserId
        );
        self::assertNotNull(
            $incident->closedAt
        );
    }

    /** @return array<string, array{bool}> */
    public static function dataProvider(): array
    {
        return [
            'closes with authenticated user'         => [false],
            'repeated close preserves original user' => [true],
        ];
    }
}
