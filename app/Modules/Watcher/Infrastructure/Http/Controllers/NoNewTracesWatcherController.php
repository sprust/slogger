<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\NoNewTracesWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\NoNewTracesWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;

readonly class NoNewTracesWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): NoNewTracesWatcherSettingsResource
    {
        $settings = $this->watcherSettings(
            type: WatcherTypeEnum::NoNewTraces,
            id: $id,
        );

        if (!$settings instanceof NoNewTracesSettingsObject) {
            throw new WatcherNotFoundException($id);
        }

        return new NoNewTracesWatcherSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(NoNewTracesWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(
            type: WatcherTypeEnum::NoNewTraces,
            validated: $request->validated(),
        );
    }

    public function update(int $id, NoNewTracesWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::NoNewTraces,
            id: $id,
            validated: $request->validated(),
        );
    }
}
