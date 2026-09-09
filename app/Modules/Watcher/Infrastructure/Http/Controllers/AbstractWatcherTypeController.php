<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Actions\Mutations\CreateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\UpdateWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use App\Modules\Watcher\Parameters\CreateWatcherParameters;
use App\Modules\Watcher\Parameters\UpdateWatcherParameters;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

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
        private CreateWatcherAction $createWatcherAction,
        private UpdateWatcherAction $updateWatcherAction,
        private FindWatcherAction $findWatcherAction
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
        return $this->findWatcher($type, $id)->settings;
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function createWatcher(WatcherTypeEnum $type, array $validated): WatcherResource
    {
        return new WatcherResource(
            $this->createWatcherAction->handle(
                new CreateWatcherParameters(
                    name: ArrayValueGetter::string($validated, 'name'),
                    type: $type,
                    enabled: ArrayValueGetter::bool($validated, 'enabled'),
                    cooldownSeconds: ArrayValueGetter::int($validated, 'cooldown_seconds'),
                    settings: $this->settingsOf($validated)
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $validated
     */
    protected function updateWatcher(WatcherTypeEnum $type, int $id, array $validated): void
    {
        $this->findWatcher($type, $id);

        try {
            $this->updateWatcherAction->handle(
                new UpdateWatcherParameters(
                    id: $id,
                    name: ArrayValueGetter::string($validated, 'name'),
                    enabled: ArrayValueGetter::bool($validated, 'enabled'),
                    cooldownSeconds: ArrayValueGetter::int($validated, 'cooldown_seconds'),
                    settings: $this->settingsOf($validated)
                )
            );
        } catch (WatcherNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }
    }

    private function findWatcher(WatcherTypeEnum $type, int $id): WatcherObject
    {
        $watcher = $this->findWatcherAction->handle($id);

        if (is_null($watcher) || $watcher->type !== $type) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return $watcher;
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
