<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Notification\Domain\Actions\Mutations\CreateChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\DeleteChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\SendTestNotificationAction;
use App\Modules\Notification\Domain\Actions\Mutations\UpdateChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelTypesAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelsAction;
use App\Modules\Notification\Domain\Services\ChannelFactory;
use App\Modules\Notification\Domain\Services\Senders\TelegramSender;
use App\Modules\Notification\Domain\Services\Types\NotificationChannelTypeRegistry;
use App\Modules\Notification\Domain\Services\Types\TelegramChannelType;
use App\Modules\Notification\Repositories\ChannelRepository;
use GuzzleHttp\Psr7\HttpFactory;
use SConcur\Features\HttpClient\HttpClient;
use SConcur\Features\HttpClient\HttpClientOptions;

class NotificationServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(
            TelegramSender::class,
            static fn(): TelegramSender => new TelegramSender(
                httpClient: new HttpClient(
                    responseFactory: new HttpFactory(),
                    options: new HttpClientOptions(
                        requestTimeoutMs: 10_000,
                        connectTimeoutMs: 3_000,
                        responseHeaderTimeoutMs: 5_000
                    )
                )
            )
        );

        parent::boot();
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            ChannelRepository::class,
            // domain services
            TelegramChannelType::class,
            NotificationChannelTypeRegistry::class,
            ChannelFactory::class,
            // actions
            FindChannelsAction::class,
            FindChannelAction::class,
            FindChannelTypesAction::class,
            CreateChannelAction::class,
            UpdateChannelAction::class,
            DeleteChannelAction::class,
            SendTestNotificationAction::class,
        ];
    }
}
