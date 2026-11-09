<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\SlowTracesWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\SlowTracesWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;

readonly class SlowTracesWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): SlowTracesWatcherSettingsResource
    {
        $settings = $this->watcherSettings(
            type: WatcherTypeEnum::SlowTraces,
            id: $id,
        );

        if (!$settings instanceof SlowTracesSettingsObject) {
            throw new WatcherNotFoundException($id);
        }

        return new SlowTracesWatcherSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(SlowTracesWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(
            type: WatcherTypeEnum::SlowTraces,
            validated: $request->validated(),
        );
    }

    public function update(int $id, SlowTracesWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::SlowTraces,
            id: $id,
            validated: $request->validated(),
        );
    }
}
