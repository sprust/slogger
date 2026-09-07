<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\TracesSpikeSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\TracesSpikeWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\TracesSpikeWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class TracesSpikeWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): TracesSpikeWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::TracesSpike, $id);

        if (!$settings instanceof TracesSpikeSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new TracesSpikeWatcherSettingsResource($id, $settings);
    }

    public function create(TracesSpikeWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::TracesSpike, $request->validated());
    }

    public function update(int $id, TracesSpikeWatcherRequest $request): void
    {
        $this->updateWatcher($id, $request->validated());
    }
}
