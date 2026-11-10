<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Domain\UseCases\DeleteChannelByAdminUseCase;
use App\Modules\Notification\Domain\UseCases\SendTestNotificationByAdminUseCase;
use App\Modules\Notification\Domain\UseCases\FindChannelTypesByAdminUseCase;
use App\Modules\Notification\Domain\UseCases\FindChannelsByAdminUseCase;
use App\Modules\Notification\Domain\UseCases\FindNotificationsByAdminUseCase;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelTypeResource;
use App\Modules\Notification\Infrastructure\Http\Resources\NotificationResource;
use App\Modules\Notification\Infrastructure\Http\Resources\SendResultResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class NotificationChannelController
{
    private const int DELIVERIES_LIMIT = 50;

    public function __construct(
        private FindChannelsByAdminUseCase $findChannelsByAdminUseCase,
        private FindChannelTypesByAdminUseCase $findChannelTypesByAdminUseCase,
        private FindNotificationsByAdminUseCase $findNotificationsByAdminUseCase,
        private DeleteChannelByAdminUseCase $deleteChannelByAdminUseCase,
        private SendTestNotificationByAdminUseCase $sendTestNotificationByAdminUseCase
    ) {
    }

    #[OaListItemTypeAttribute(ChannelResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ChannelResource::collection(
            $this->findChannelsByAdminUseCase->handle()
        );
    }

    #[OaListItemTypeAttribute(ChannelTypeResource::class)]
    public function types(): AnonymousResourceCollection
    {
        return ChannelTypeResource::collection(
            $this->findChannelTypesByAdminUseCase->handle()
        );
    }

    #[OaListItemTypeAttribute(NotificationResource::class)]
    public function deliveries(int $id): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $this->findNotificationsByAdminUseCase->handle(
                channelId: $id,
                limit: self::DELIVERIES_LIMIT,
            )
        );
    }

    public function test(int $id): SendResultResource
    {
        return new SendResultResource(
            $this->sendTestNotificationByAdminUseCase->handle($id)
        );
    }

    public function delete(int $id): void
    {
        $this->deleteChannelByAdminUseCase->handle($id);
    }
}
