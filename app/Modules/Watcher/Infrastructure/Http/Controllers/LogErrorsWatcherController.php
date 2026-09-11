<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\LogErrorsWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\LogErrorsWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class LogErrorsWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): LogErrorsWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::LogErrors, $id);

        if (!$settings instanceof LogErrorsSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new LogErrorsWatcherSettingsResource($id, $settings);
    }

    public function create(LogErrorsWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::LogErrors, $request->validated());
    }

    public function update(int $id, LogErrorsWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::LogErrors,
            id: $id,
            validated: $request->validated()
        );
    }
}
