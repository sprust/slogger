<?php

namespace Tests\Modules\Notification\Infrastructure\Jobs;

use App\Modules\Notification\Domain\Actions\Mutations\SendNotificationAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindNotificationAction;
use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Entities\SendResultObject;
use App\Modules\Notification\Infrastructure\Jobs\SendNotificationJob;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Modules\Notification\NotificationFactoryTrait;

class SendNotificationJobTest extends TestCase
{
    use NotificationFactoryTrait;

    public function testADeliveryIsTheEndOfIt(): void
    {
        $queueJob = $this->createMock(QueueJob::class);
        $queueJob->expects($this->never())->method('release');
        $queueJob->expects($this->never())->method('fail');

        $this->job(
            $this->notification(),
            new SendResultObject(delivered: true),
            $queueJob
        )->handle(...$this->dependencies($this->notification(), new SendResultObject(delivered: true)));

        $this->assertTrue(true);
    }

    public function testARefusalFailsTheJobRatherThanRetrying(): void
    {
        $queueJob = $this->createMock(QueueJob::class);
        $queueJob->expects($this->once())->method('fail');
        $queueJob->expects($this->never())->method('release');

        $result = new SendResultObject(delivered: false, permanent: true, error: 'Unauthorized');

        $this->job($this->notification(), $result, $queueJob)
            ->handle(...$this->dependencies($this->notification(), $result));
    }

    public function testAWaitTheSenderAskedForIsHonoured(): void
    {
        $queueJob = $this->createMock(QueueJob::class);
        $queueJob->expects($this->once())->method('release')->with(37);

        $result = new SendResultObject(
            delivered: false,
            error: 'Too Many Requests',
            retryAfterSeconds: 37
        );

        $this->job($this->notification(), $result, $queueJob)
            ->handle(...$this->dependencies($this->notification(), $result));
    }

    public function testAnOrdinaryFailureIsThrownForTheQueueToHandle(): void
    {
        $result = new SendResultObject(delivered: false, error: 'Bad Gateway');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bad Gateway');

        $this->job($this->notification(), $result, $this->createMock(QueueJob::class))
            ->handle(...$this->dependencies($this->notification(), $result));
    }

    public function testARowThatIsNoLongerThereIsLetGo(): void
    {
        $sendAction = $this->createMock(SendNotificationAction::class);
        $sendAction->expects($this->never())->method('handle');

        new SendNotificationJob('68be1f000000000000000001')->handle(
            $this->findNotificationAction(null),
            $this->createMock(FindChannelAction::class),
            $sendAction
        );
    }

    public function testARowAlreadyDeliveredIsNotSentAgain(): void
    {
        $sendAction = $this->createMock(SendNotificationAction::class);
        $sendAction->expects($this->never())->method('handle');

        new SendNotificationJob('68be1f000000000000000001')->handle(
            $this->findNotificationAction($this->notification(sentAt: Carbon::now())),
            $this->createMock(FindChannelAction::class),
            $sendAction
        );
    }

    private function job(
        NotificationObject $notification,
        SendResultObject $result,
        QueueJob $queueJob
    ): SendNotificationJob {
        $job = new SendNotificationJob($notification->id);

        $job->setJob($queueJob);

        return $job;
    }

    /**
     * @return array{FindNotificationAction, FindChannelAction, SendNotificationAction}
     */
    private function dependencies(NotificationObject $notification, SendResultObject $result): array
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);
        $findChannelAction->method('handle')->willReturn($this->channel());

        $sendAction = $this->createMock(SendNotificationAction::class);
        $sendAction->method('handle')->willReturn($result);

        return [$this->findNotificationAction($notification), $findChannelAction, $sendAction];
    }

    private function findNotificationAction(?NotificationObject $notification): FindNotificationAction
    {
        $action = $this->createMock(FindNotificationAction::class);
        $action->method('handle')->willReturn($notification);

        return $action;
    }
}
