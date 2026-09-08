<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Domain\Actions\Mutations\DeleteChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\SendTestNotificationAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelTypesAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelsAction;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelTypeResource;
use App\Modules\Notification\Infrastructure\Http\Resources\SendResultResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class NotificationChannelController
{
    public function __construct(
        private FindChannelsAction $findChannelsAction,
        private FindChannelTypesAction $findChannelTypesAction,
        private DeleteChannelAction $deleteChannelAction,
        private SendTestNotificationAction $sendTestNotificationAction
    ) {
    }

    #[OaListItemTypeAttribute(ChannelResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ChannelResource::collection(
            $this->findChannelsAction->handle()
        );
    }

    #[OaListItemTypeAttribute(ChannelTypeResource::class)]
    public function types(): AnonymousResourceCollection
    {
        return ChannelTypeResource::collection(
            $this->findChannelTypesAction->handle()
        );
    }

    public function test(int $id): SendResultResource
    {
        try {
            return new SendResultResource(
                $this->sendTestNotificationAction->handle($id)
            );
        } catch (NotificationChannelNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }
    }

    public function delete(int $id): void
    {
        $this->deleteChannelAction->handle($id);
    }
}
