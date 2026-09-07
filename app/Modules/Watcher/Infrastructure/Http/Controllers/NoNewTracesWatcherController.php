<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\NoNewTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\NoNewTracesWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\NoNewTracesWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class NoNewTracesWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): NoNewTracesWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::NoNewTraces, $id);

        if (!$settings instanceof NoNewTracesSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new NoNewTracesWatcherSettingsResource($id, $settings);
    }

    public function create(NoNewTracesWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::NoNewTraces, $request->validated());
    }

    public function update(int $id, NoNewTracesWatcherRequest $request): void
    {
        $this->updateWatcher($id, $request->validated());
    }
}
