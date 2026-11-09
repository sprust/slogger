<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\ManyTracesWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\ManyTracesWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;

readonly class ManyTracesWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): ManyTracesWatcherSettingsResource
    {
        $settings = $this->watcherSettings(
            type: WatcherTypeEnum::ManyTraces,
            id: $id,
        );

        if (!$settings instanceof ManyTracesSettingsObject) {
            throw new WatcherNotFoundException($id);
        }

        return new ManyTracesWatcherSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(ManyTracesWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(
            type: WatcherTypeEnum::ManyTraces,
            validated: $request->validated(),
        );
    }

    public function update(int $id, ManyTracesWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::ManyTraces,
            id: $id,
            validated: $request->validated(),
        );
    }
}
