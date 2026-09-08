<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\BufferOverflowWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\BufferOverflowWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class BufferOverflowWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): BufferOverflowWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::BufferOverflow, $id);

        if (!$settings instanceof BufferOverflowSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new BufferOverflowWatcherSettingsResource($id, $settings);
    }

    public function create(BufferOverflowWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::BufferOverflow, $request->validated());
    }

    public function update(int $id, BufferOverflowWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::BufferOverflow,
            id: $id,
            validated: $request->validated()
        );
    }
}
