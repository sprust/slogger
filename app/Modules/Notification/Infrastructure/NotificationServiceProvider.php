<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure;

use App\Modules\Notification\Infrastructure\Exceptions\NotificationExceptionManager;
use App\Modules\Notification\Infrastructure\Jobs\SendNotificationJob;
use App\Modules\Notification\Domain\Events\NotificationEnqueuedEvent;
use App\Modules\Notification\Infrastructure\Listeners\DispatchNotificationListener;
use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Notification\Infrastructure\Listeners\EnqueueNotificationsListener;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use App\Modules\Notification\Domain\Actions\Mutations\CreateChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\DeleteChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\EnqueueNotificationsAction;
use App\Modules\Notification\Domain\Actions\Mutations\SendNotificationAction;
use App\Modules\Notification\Domain\Actions\Mutations\SendTestNotificationAction;
use App\Modules\Notification\Domain\Actions\Mutations\UpdateChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelTypesAction;
use App\Modules\Notification\Domain\Actions\Queries\FindNotificationAction;
use App\Modules\Notification\Domain\Actions\Queries\FindNotificationsAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelsAction;
use App\Modules\Notification\Domain\Services\ChannelFactory;
use App\Modules\Notification\Domain\Services\IncidentMessageFactory;
use App\Modules\Notification\Domain\Services\Senders\HttpSendResultReader;
use App\Modules\Notification\Domain\Services\Senders\SlackSender;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Domain\Services\Senders\WebhookSender;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Domain\Services\Types\SlackChannelType;
use App\Modules\Notification\Domain\Services\Types\TelegramChannelType;
use App\Modules\Notification\Domain\Services\Types\WebhookChannelType;
use App\Modules\Notification\Repositories\ChannelRepository;
use App\Modules\Notification\Repositories\NotificationRepository;
use GuzzleHttp\Psr7\HttpFactory;
use SConcur\Features\HttpClient\HttpClient;
use Illuminate\Contracts\Foundation\Application;
use SConcur\Features\HttpClient\HttpClientOptions;
use Illuminate\Console\Scheduling\Schedule;

class NotificationServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
    }

    protected function onRegister(): void
    {
        $this->app->singleton(
            abstract: TelegramSender::class,
            concrete: static fn(): TelegramSender => new TelegramSender(
                self::makeHttpClient(),
            ),
        );

        $this->app->singleton(
            abstract: SlackSender::class,
            concrete: static fn(Application $app): SlackSender => new SlackSender(
                httpClient: self::makeHttpClient(),
                resultReader: $app->make(
                    abstract: HttpSendResultReader::class,
                ),
            ),
        );

        $this->app->singleton(
            abstract: WebhookSender::class,
            concrete: static fn(Application $app): WebhookSender => new WebhookSender(
                httpClient: self::makeHttpClient(),
                resultReader: $app->make(
                    abstract: HttpSendResultReader::class,
                ),
            ),
        );

        $this->app->singleton(
            abstract: IncidentMessageFactory::class,
            concrete: static fn(Application $app): IncidentMessageFactory => new IncidentMessageFactory(
                (string) config('app.name'),
            ),
        );
    }

    protected function getJobs(): array
    {
        return [
            SendNotificationJob::class,
        ];
    }

    protected function getListeners(): array
    {
        return [
            NotificationEnqueuedEvent::class   => [DispatchNotificationListener::class],
            WatcherIncidentChangedEvent::class => [EnqueueNotificationsListener::class],
        ];
    }

    protected function getContracts(): array
    {
        return [
            ChannelRepository::class,
            NotificationRepository::class,
            HttpSendResultReader::class,
            TelegramChannelType::class,
            SlackChannelType::class,
            WebhookChannelType::class,
            NotificationChannelTypeRegistry::class,
            ChannelFactory::class,
            FindChannelsAction::class,
            FindChannelAction::class,
            FindChannelTypesAction::class,
            FindNotificationAction::class,
            FindNotificationsAction::class,
            CreateChannelAction::class,
            UpdateChannelAction::class,
            DeleteChannelAction::class,
            SendTestNotificationAction::class,
            SendNotificationAction::class,
            EnqueueNotificationsAction::class,
        ];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return NotificationExceptionManager::class;
    }

    protected function getCommands(): array
    {
        return [];
    }

    private static function makeHttpClient(): HttpClient
    {
        return new HttpClient(
            responseFactory: new HttpFactory(),
            options: new HttpClientOptions(
                requestTimeoutMs: 10000,
                connectTimeoutMs: 3000,
                responseHeaderTimeoutMs: 5000,
            ),
        );
    }
}
