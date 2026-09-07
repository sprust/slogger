<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\Actions\Mutations\DeleteWatcherAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherTypesAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatchersAction;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherTypeResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * What is the same for every watcher: listing them, describing the types, removing one.
 *
 * Creating and editing are not here. Those take a body whose shape follows the type, so
 * each type has a route, a request and a controller of its own — see
 * AbstractWatcherTypeController.
 */
readonly class WatcherController
{
    public function __construct(
        private FindWatchersAction $findWatchersAction,
        private FindWatcherTypesAction $findWatcherTypesAction,
        private DeleteWatcherAction $deleteWatcherAction
    ) {
    }

    /**
     * @return WatcherResource[]
     */
    #[OaListItemTypeAttribute(WatcherResource::class)]
    public function index(): array
    {
        return WatcherResource::mapIntoMe($this->findWatchersAction->handle());
    }

    /**
     * The types, their titles and the defaults their fields start from, so the panel does
     * not carry a second copy of any of it.
     *
     * @return WatcherTypeResource[]
     */
    #[OaListItemTypeAttribute(WatcherTypeResource::class)]
    public function types(): array
    {
        return WatcherTypeResource::mapIntoMe($this->findWatcherTypesAction->handle());
    }

    public function delete(int $id): void
    {
        $this->deleteWatcherAction->handle($id);
    }
}
