<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Entities\Settings\LogErrorsSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\LogErrorsWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\LogErrorsWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;

readonly class ReceiverErrorsWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): LogErrorsWatcherSettingsResource
    {
        $settings = $this->watcherSettings(
            type: WatcherTypeEnum::ReceiverErrors,
            id: $id,
        );

        if (!$settings instanceof LogErrorsSettingsObject) {
            throw new WatcherNotFoundException($id);
        }

        return new LogErrorsWatcherSettingsResource(
            id: $id,
            resource: $settings,
        );
    }

    public function create(LogErrorsWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(
            type: WatcherTypeEnum::ReceiverErrors,
            validated: $request->validated(),
        );
    }

    public function update(int $id, LogErrorsWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::ReceiverErrors,
            id: $id,
            validated: $request->validated(),
        );
    }
}
