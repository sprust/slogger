<?php

declare(strict_types=1);

namespace Tests\Modules\Notification\Integration;

use Tests\Modules\BaseModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Modules\Notification\Domain\UseCases\FindNotificationsByAdminUseCase;
use App\Modules\Notification\Repositories\NotificationRepository;
use App\Modules\Notification\Enums\NotificationKindEnum;
use Tests\Factories\Entities\ChannelFactory;
use Tests\Factories\Entities\NotificationFactory;

class NotificationDatabaseTest extends BaseModuleTestCase
{
    #[DataProvider('dataProvider')]
    public function test(NotificationKindEnum $kind): void
    {
        $findNotificationsByAdminUseCase = $this->appMake(FindNotificationsByAdminUseCase::class);
        $notificationFactory             = $this->appMake(NotificationFactory::class);

        $channelId      = $this->appMake(ChannelFactory::class)->create();
        $notificationId = $notificationFactory->create(
            channelId: $channelId,
            kind: $kind,
        );
        $notificationFactory->create();

        $notifications = $findNotificationsByAdminUseCase->handle(
            channelId: $channelId,
            limit: 10,
        );

        self::assertCount(
            expectedCount: 1,
            haystack: $notifications
        );
        self::assertSame(
            expected: $notificationId,
            actual: $notifications[0]->id
        );
        self::assertSame(
            expected: $kind,
            actual: $notifications[0]->kind
        );
        self::assertEquals(
            expected: $this->appMake(NotificationRepository::class)->findById($notificationId),
            actual: $notifications[0]
        );
    }

    /** @return array<string, array{NotificationKindEnum}> */
    public static function dataProvider(): array
    {
        return [
            'opened notification' => [
                NotificationKindEnum::Opened,
            ],
            'event notification' => [
                NotificationKindEnum::Event,
            ],
            'closed notification' => [
                NotificationKindEnum::Closed,
            ],
        ];
    }
}
