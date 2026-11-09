<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Notification\Domain\UseCases\CreateChannelByAdminUseCase;
use App\Modules\Notification\Domain\UseCases\UpdateChannelByAdminUseCase;
use App\Modules\Notification\Domain\UseCases\FindChannelByAdminUseCase;
use App\Modules\Notification\Entities\Settings\ChannelSettingsInterface;
use App\Modules\Notification\Enums\NotificationChannelTypeEnum;
use App\Modules\Notification\Infrastructure\Http\Resources\ChannelResource;
use App\Modules\Notification\Parameters\CreateChannelParameters;
use App\Modules\Notification\Parameters\UpdateChannelParameters;

abstract readonly class AbstractChannelTypeController
{
    public function __construct(private CreateChannelByAdminUseCase $createChannelByAdminUseCase, private UpdateChannelByAdminUseCase $updateChannelByAdminUseCase, private FindChannelByAdminUseCase $findChannelByAdminUseCase)
    {
    }

    protected function channelSettings(NotificationChannelTypeEnum $type, int $id): ChannelSettingsInterface
    {
        return $this->findChannelByAdminUseCase->handle(
            id: $id,
            type: $type,
        )->settings;
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function createChannel(NotificationChannelTypeEnum $type, array $validated): ChannelResource
    {
        return new ChannelResource(
            $this->createChannelByAdminUseCase->handle(
                new CreateChannelParameters(
                    name: ArrayValueGetter::string(
                        data: $validated,
                        key: 'name',
                    ),
                    type: $type,
                    enabled: ArrayValueGetter::bool(
                        data: $validated,
                        key: 'enabled',
                    ),
                    settings: $this->settingsOf($validated),
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function updateChannel(NotificationChannelTypeEnum $type, int $id, array $validated): void
    {
        $this->updateChannelByAdminUseCase->handle(
            parameters: new UpdateChannelParameters(
                id: $id,
                name: ArrayValueGetter::string(
                    data: $validated,
                    key: 'name',
                ),
                enabled: ArrayValueGetter::bool(
                    data: $validated,
                    key: 'enabled',
                ),
                settings: $this->settingsOf($validated),
            ),
            type: $type,
        );
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
