<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\UseCases\CreateWatcherByAdminUseCase;
use App\Modules\Watcher\Domain\UseCases\UpdateWatcherByAdminUseCase;
use App\Modules\Watcher\Domain\UseCases\FindWatcherByAdminUseCase;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use App\Modules\Watcher\Parameters\CreateWatcherParameters;
use App\Modules\Watcher\Parameters\UpdateWatcherParameters;

/**
 * What every watcher type's controller does with a body that has already been validated
 * against that type's own rules.
 *
 * There is a controller per type because there is a request per type: the generated
 * schema then says which numbers a watcher of this type takes, instead of offering every
 * number any type might take and accepting whichever arrive. A field misspelt in the
 * panel used to be dropped in validation and filled with a default nobody chose — a
 * watcher measuring something else, quietly.
 */
abstract readonly class AbstractWatcherTypeController
{
    public function __construct(
        private CreateWatcherByAdminUseCase $createWatcherByAdminUseCase,
        private UpdateWatcherByAdminUseCase $updateWatcherByAdminUseCase,
        private FindWatcherByAdminUseCase $findWatcherByAdminUseCase
    ) {
    }

    /**
     * The settings of one watcher of this type.
     *
     * A watcher of another type answers 404 rather than a resource of the wrong shape: the
     * route names a type, and that is the promise the answer keeps.
     */
    protected function watcherSettings(WatcherTypeEnum $type, int $id): WatcherSettingsInterface
    {
        return $this->findWatcherByAdminUseCase->handle(
            id: $id,
            type: $type,
        )->settings;
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function createWatcher(WatcherTypeEnum $type, array $validated): WatcherResource
    {
        return new WatcherResource(
            $this->createWatcherByAdminUseCase->handle(
                new CreateWatcherParameters(
                    name: ArrayValueGetter::string(
                        data: $validated,
                        key: 'name',
                    ),
                    type: $type,
                    enabled: ArrayValueGetter::bool(
                        data: $validated,
                        key: 'enabled',
                    ),
                    cooldownSeconds: ArrayValueGetter::int(
                        data: $validated,
                        key: 'cooldown_seconds',
                    ),
                    notificationChannelId: ArrayValueGetter::intNull(
                        data: $validated,
                        key: 'notification_channel_id',
                    ),
                    notifyOnOpened: ArrayValueGetter::bool(
                        data: $validated,
                        key: 'notify_on_opened',
                    ),
                    notifyOnEvent: ArrayValueGetter::bool(
                        data: $validated,
                        key: 'notify_on_event',
                    ),
                    notifyOnClosed: ArrayValueGetter::bool(
                        data: $validated,
                        key: 'notify_on_closed',
                    ),
                    settings: $this->settingsOf($validated),
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function updateWatcher(WatcherTypeEnum $type, int $id, array $validated): void
    {
        $this->updateWatcherByAdminUseCase->handle(
            parameters: new UpdateWatcherParameters(
                id: $id,
                name: ArrayValueGetter::string(
                    data: $validated,
                    key: 'name',
                ),
                enabled: ArrayValueGetter::bool(
                    data: $validated,
                    key: 'enabled',
                ),
                cooldownSeconds: ArrayValueGetter::int(
                    data: $validated,
                    key: 'cooldown_seconds',
                ),
                notificationChannelId: ArrayValueGetter::intNull(
                    data: $validated,
                    key: 'notification_channel_id',
                ),
                notifyOnOpened: ArrayValueGetter::bool(
                    data: $validated,
                    key: 'notify_on_opened',
                ),
                notifyOnEvent: ArrayValueGetter::bool(
                    data: $validated,
                    key: 'notify_on_event',
                ),
                notifyOnClosed: ArrayValueGetter::bool(
                    data: $validated,
                    key: 'notify_on_closed',
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
