<?php

namespace Tests\Modules\Notification\Domain\Actions;

use App\Modules\Notification\Domain\Actions\Mutations\SendNotificationAction;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Domain\Services\Types\TelegramChannelType;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Repositories\NotificationRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Notification\NotificationFactoryTrait;

class SendNotificationActionTest extends TestCase
{
    use NotificationFactoryTrait;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-08 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testADeliveredRowIsClosed(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('markSent')
            ->with('68be1f000000000000000001', Carbon::now());

        $repository->expects($this->never())->method('markFailed');

        $result = $this->action($repository, new SendResultObject(delivered: true))
            ->handle($this->notification(), $this->channel());

        $this->assertTrue($result->delivered);
    }

    public function testTheTextGoesOutAsItWasComposed(): void
    {
        $sender = $this->createMock(TelegramSender::class);

        $sender->expects($this->once())
            ->method('send')
            ->with($this->channel(), 'a watcher went off')
            ->willReturn(new SendResultObject(delivered: true));

        new SendNotificationAction(
            types: new NotificationChannelTypeRegistry(new TelegramChannelType($sender)),
            notificationRepository: $this->createMock(NotificationRepository::class)
        )->handle($this->notification(), $this->channel());
    }

    public function testAFailureIsWrittenDownAndHandedBack(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('markFailed')
            ->with('68be1f000000000000000001', 'boom');

        $result = $this->action($repository, new SendResultObject(delivered: false, error: 'boom'))
            ->handle($this->notification(), $this->channel());

        $this->assertFalse($result->delivered);
        $this->assertFalse($result->permanent);
    }

    public function testARefusalIsHandedBackAsPermanent(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('markFailed')
            ->with('68be1f000000000000000001', 'Unauthorized');

        $result = $this->action(
            $repository,
            new SendResultObject(delivered: false, permanent: true, error: 'Unauthorized')
        )->handle($this->notification(), $this->channel());

        $this->assertTrue($result->permanent);
    }

    public function testARowWhoseChannelIsGoneIsRefusedWithoutASend(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('markFailed')
            ->with('68be1f000000000000000001', 'Channel [42] is gone');

        $sender = $this->createMock(TelegramSender::class);
        $sender->expects($this->never())->method('send');

        $result = new SendNotificationAction(
            types: new NotificationChannelTypeRegistry(new TelegramChannelType($sender)),
            notificationRepository: $repository
        )->handle($this->notification(channelId: 42), null);

        $this->assertTrue($result->permanent);
    }

    public function testARowWhoseChannelIsDisabledIsRefusedWithoutASend(): void
    {
        $repository = $this->createMock(NotificationRepository::class);

        $repository->expects($this->once())
            ->method('markFailed')
            ->with('68be1f000000000000000001', 'Channel [1] is disabled');

        $sender = $this->createMock(TelegramSender::class);
        $sender->expects($this->never())->method('send');

        $result = new SendNotificationAction(
            types: new NotificationChannelTypeRegistry(new TelegramChannelType($sender)),
            notificationRepository: $repository
        )->handle($this->notification(), $this->channel(enabled: false));

        $this->assertTrue($result->permanent);
    }

    private function action(
        NotificationRepository $repository,
        SendResultObject $result
    ): SendNotificationAction {
        $sender = $this->createMock(TelegramSender::class);
        $sender->method('send')->willReturn($result);

        return new SendNotificationAction(
            types: new NotificationChannelTypeRegistry(new TelegramChannelType($sender)),
            notificationRepository: $repository
        );
    }
}
