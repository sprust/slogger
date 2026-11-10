<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\BufferOverflowWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\BufferOverflowWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;

readonly class BufferOverflowWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): BufferOverflowWatcherSettingsResource
    {
        $settings = $this->watcherSettings(
            type: WatcherTypeEnum::BufferOverflow,
            id: $id,
        );

        if (!$settings instanceof BufferOverflowSettingsObject) {
            throw new WatcherNotFoundException($id);
        }

        return new BufferOverflowWatcherSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(BufferOverflowWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(
            type: WatcherTypeEnum::BufferOverflow,
            validated: $request->validated(),
        );
    }

    public function update(int $id, BufferOverflowWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::BufferOverflow,
            id: $id,
            validated: $request->validated(),
        );
    }
}
