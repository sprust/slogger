<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\SlowTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\SlowTracesWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\SlowTracesWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class SlowTracesWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): SlowTracesWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::SlowTraces, $id);

        if (!$settings instanceof SlowTracesSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new SlowTracesWatcherSettingsResource($id, $settings);
    }

    public function create(SlowTracesWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::SlowTraces, $request->validated());
    }

    public function update(int $id, SlowTracesWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::SlowTraces,
            id: $id,
            validated: $request->validated()
        );
    }
}
