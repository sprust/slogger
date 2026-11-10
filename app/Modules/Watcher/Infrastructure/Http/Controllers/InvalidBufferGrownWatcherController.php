<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\InvalidBufferGrownWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\InvalidBufferGrownWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;

readonly class InvalidBufferGrownWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): InvalidBufferGrownWatcherSettingsResource
    {
        $settings = $this->watcherSettings(
            type: WatcherTypeEnum::InvalidBufferGrown,
            id: $id,
        );

        if (!$settings instanceof InvalidBufferGrownSettingsObject) {
            throw new WatcherNotFoundException($id);
        }

        return new InvalidBufferGrownWatcherSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(InvalidBufferGrownWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(
            type: WatcherTypeEnum::InvalidBufferGrown,
            validated: $request->validated(),
        );
    }

    public function update(int $id, InvalidBufferGrownWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::InvalidBufferGrown,
            id: $id,
            validated: $request->validated(),
        );
    }
}
