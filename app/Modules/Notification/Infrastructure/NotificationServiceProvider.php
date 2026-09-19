<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
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
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use GuzzleHttp\Psr7\HttpFactory;
use SConcur\Features\HttpClient\HttpClient;
use Illuminate\Contracts\Foundation\Application;
use SConcur\Features\HttpClient\HttpClientOptions;

class NotificationServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(
            TelegramSender::class,
            static fn(): TelegramSender => new TelegramSender(
                httpClient: self::makeHttpClient()
            )
        );

        $this->app->singleton(
            SlackSender::class,
            static fn(Application $app): SlackSender => new SlackSender(
                httpClient: self::makeHttpClient(),
                resultReader: $app->make(HttpSendResultReader::class)
            )
        );

        $this->app->singleton(
            WebhookSender::class,
            static fn(Application $app): WebhookSender => new WebhookSender(
                httpClient: self::makeHttpClient(),
                resultReader: $app->make(HttpSendResultReader::class)
            )
        );

        $this->app->singleton(
            IncidentMessageFactory::class,
            static fn(Application $app): IncidentMessageFactory => new IncidentMessageFactory(
                watcherTypes: $app->make(WatcherTypeRegistry::class),
                appName: (string) config('app.name')
            )
        );

        parent::boot();
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

    private static function makeHttpClient(): HttpClient
    {
        return new HttpClient(
            responseFactory: new HttpFactory(),
            options: new HttpClientOptions(
                requestTimeoutMs: 10_000,
                connectTimeoutMs: 3_000,
                responseHeaderTimeoutMs: 5_000
            )
        );
    }
}
