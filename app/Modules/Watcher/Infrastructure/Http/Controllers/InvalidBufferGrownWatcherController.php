<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\InvalidBufferGrownWatcherRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\InvalidBufferGrownWatcherSettingsResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class InvalidBufferGrownWatcherController extends AbstractWatcherTypeController
{
    public function show(int $id): InvalidBufferGrownWatcherSettingsResource
    {
        $settings = $this->watcherSettings(WatcherTypeEnum::InvalidBufferGrown, $id);

        if (!$settings instanceof InvalidBufferGrownSettingsObject) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher [$id] not found");
        }

        return new InvalidBufferGrownWatcherSettingsResource($id, $settings);
    }

    public function create(InvalidBufferGrownWatcherRequest $request): WatcherResource
    {
        return $this->createWatcher(WatcherTypeEnum::InvalidBufferGrown, $request->validated());
    }

    public function update(int $id, InvalidBufferGrownWatcherRequest $request): void
    {
        $this->updateWatcher(
            type: WatcherTypeEnum::InvalidBufferGrown,
            id: $id,
            validated: $request->validated()
        );
    }
}
