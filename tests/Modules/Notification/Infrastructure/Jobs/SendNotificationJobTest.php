<?php

namespace Tests\Modules\Notification\Infrastructure\Jobs;

use App\Modules\Notification\Domain\UseCases\SendNotificationUseCase;
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

        $queueJob->expects(
            $this->never()
        )->method('release');

        $queueJob->expects(
            $this->never()
        )->method('fail');

        $this
            ->job(
                notification: $this->notification(),
                result: new SendResultObject(
                    delivered: true,
                ),
                queueJob: $queueJob,
            )
            ->handle(
                ...$this->dependencies(
                    notification: $this->notification(),
                    result: new SendResultObject(
                        delivered: true,
                    ),
                )
            );
    }

    public function testARefusalFailsTheJobRatherThanRetrying(): void
    {
        $queueJob = $this->createMock(QueueJob::class);

        $queueJob->expects(
            $this->once()
        )->method('fail');

        $queueJob->expects(
            $this->never()
        )->method('release');

        $result = new SendResultObject(
            delivered: false,
            permanent: true,
            error: 'Unauthorized',
        );

        $this
            ->job(
                notification: $this->notification(),
                result: $result,
                queueJob: $queueJob,
            )
            ->handle(
                ...$this->dependencies(
                    notification: $this->notification(),
                    result: $result,
                )
            );
    }

    public function testAWaitTheSenderAskedForIsHonoured(): void
    {
        $queueJob = $this->createMock(QueueJob::class);

        $queueJob->expects(
            $this->once()
        )->method('release')->with(37);

        $result = new SendResultObject(
            delivered: false,
            error: 'Too Many Requests',
            retryAfterSeconds: 37,
        );

        $this
            ->job(
                notification: $this->notification(),
                result: $result,
                queueJob: $queueJob,
            )
            ->handle(
                ...$this->dependencies(
                    notification: $this->notification(),
                    result: $result,
                )
            );
    }

    public function testAnOrdinaryFailureIsThrownForTheQueueToHandle(): void
    {
        $result = new SendResultObject(
            delivered: false,
            error: 'Bad Gateway',
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage('Bad Gateway');

        $this
            ->job(
                notification: $this->notification(),
                result: $result,
                queueJob: $this->createMock(QueueJob::class),
            )
            ->handle(
                ...$this->dependencies(
                    notification: $this->notification(),
                    result: $result,
                )
            );
    }

    public function testARowThatIsNoLongerThereIsLetGo(): void
    {
        $sendAction = $this->createMock(SendNotificationAction::class);

        $sendAction->expects(
            $this->never()
        )->method('handle');

        (new SendNotificationJob('68be1f000000000000000001'))->handle(
            new SendNotificationUseCase(
                sendNotificationAction: $sendAction,
                findNotificationAction: $this->findNotificationAction(null),
                findChannelAction: $this->createMock(FindChannelAction::class),
            )
        );
    }

    public function testARowAlreadyDeliveredIsNotSentAgain(): void
    {
        $sendAction = $this->createMock(SendNotificationAction::class);

        $sendAction->expects(
            $this->never()
        )->method('handle');

        (new SendNotificationJob('68be1f000000000000000001'))->handle(
            new SendNotificationUseCase(
                sendNotificationAction: $sendAction,
                findNotificationAction: $this->findNotificationAction(
                    $this->notification(
                        sentAt: Carbon::now(),
                    )
                ),
                findChannelAction: $this->createMock(FindChannelAction::class),
            )
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
     * @return array{SendNotificationUseCase}
     */
    private function dependencies(NotificationObject $notification, SendResultObject $result): array
    {
        $findChannelAction = $this->createMock(FindChannelAction::class);

        $findChannelAction->method('handle')->willReturn(
            $this->channel()
        );

        $sendAction = $this->createMock(SendNotificationAction::class);

        $sendAction->method('handle')->willReturn($result);

        return [
            new SendNotificationUseCase(
                sendNotificationAction: $sendAction,
                findNotificationAction: $this->findNotificationAction($notification),
                findChannelAction: $findChannelAction,
            ),
        ];
    }

    private function findNotificationAction(
        ?NotificationObject $notification
    ): FindNotificationAction {
        $action = $this->createMock(FindNotificationAction::class);

        $action->method('handle')->willReturn($notification);

        return $action;
    }
}
