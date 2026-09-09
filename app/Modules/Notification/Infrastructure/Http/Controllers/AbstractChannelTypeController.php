<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Notification\Domain\Actions\Mutations\CreateChannelAction;
use App\Modules\Notification\Domain\Actions\Mutations\UpdateChannelAction;
use App\Modules\Notification\Domain\Actions\Queries\FindChannelAction;
use App\Modules\Notification\Domain\Exceptions\NotificationChannelNotFoundException;
use App\Modules\Notification\Entities\ChannelObject;
use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Parameters\CreateChannelParameters;
use App\Modules\Notification\Parameters\UpdateChannelParameters;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

abstract readonly class AbstractChannelTypeController
{
    public function __construct(
        private CreateChannelAction $createChannelAction,
        private UpdateChannelAction $updateChannelAction,
        private FindChannelAction $findChannelAction
    ) {
    }

    protected function channelSettings(NotificationChannelTypeEnum $type, int $id): ChannelSettingsInterface
    {
        return $this->findChannel($type, $id)->settings;
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function createChannel(NotificationChannelTypeEnum $type, array $validated): ChannelResource
    {
        return new ChannelResource(
            $this->createChannelAction->handle(
                new CreateChannelParameters(
                    name: ArrayValueGetter::string($validated, 'name'),
                    type: $type,
                    enabled: ArrayValueGetter::bool($validated, 'enabled'),
                    onOpened: ArrayValueGetter::bool($validated, 'on_opened'),
                    onEvent: ArrayValueGetter::bool($validated, 'on_event'),
                    onClosed: ArrayValueGetter::bool($validated, 'on_closed'),
                    settings: $this->settingsOf($validated)
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function updateChannel(NotificationChannelTypeEnum $type, int $id, array $validated): void
    {
        $this->findChannel($type, $id);

        try {
            $this->updateChannelAction->handle(
                new UpdateChannelParameters(
                    id: $id,
                    name: ArrayValueGetter::string($validated, 'name'),
                    enabled: ArrayValueGetter::bool($validated, 'enabled'),
                    onOpened: ArrayValueGetter::bool($validated, 'on_opened'),
                    onEvent: ArrayValueGetter::bool($validated, 'on_event'),
                    onClosed: ArrayValueGetter::bool($validated, 'on_closed'),
                    settings: $this->settingsOf($validated)
                )
            );
        } catch (NotificationChannelNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }
    }

    private function findChannel(NotificationChannelTypeEnum $type, int $id): ChannelObject
    {
        $channel = $this->findChannelAction->handle($id);

        /** @phpstan-ignore notIdentical.alwaysFalse */
        if (is_null($channel) || $channel->type !== $type) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Notification channel [$id] not found");
        }

        return $channel;
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @return array<string, mixed>
     */
    private function settingsOf(array $validated): array
    {
        $settings = $validated['settings'] ?? [];

        /** @var array<string, mixed> */
        return is_array($settings) ? $settings : [];
    }
}
