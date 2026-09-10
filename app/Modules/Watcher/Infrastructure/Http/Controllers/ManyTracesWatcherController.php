<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\ManyTracesSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\ManyTracesWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\ManyTracesWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class ManyTracesWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): ManyTracesWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::ManyTraces, $id);

        if (!$settings instanceof ManyTracesSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new ManyTracesWatcherSettingsResource($id, $settings);
    }

    public function create(ManyTracesWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::ManyTraces, $request->validated());
    }

    public function update(int $id, ManyTracesWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::ManyTraces,
            id: $id,
            validated: $request->validated()
        );
    }
}
