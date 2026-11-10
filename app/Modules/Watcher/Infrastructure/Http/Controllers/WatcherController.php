<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Domain\UseCases\DeleteWatcherByAdminUseCase;
use App\Modules\Watcher\Domain\UseCases\FindWatcherTypesByAdminUseCase;
use App\Modules\Watcher\Domain\UseCases\FindWatchersByAdminUseCase;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherTypeResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        private FindWatchersByAdminUseCase $findWatchersByAdminUseCase,
        private FindWatcherTypesByAdminUseCase $findWatcherTypesByAdminUseCase,
        private DeleteWatcherByAdminUseCase $deleteWatcherByAdminUseCase
    ) {
    }

    #[OaListItemTypeAttribute(WatcherResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return WatcherResource::collection(
            $this->findWatchersByAdminUseCase->handle()
        );
    }

    /**
     * The types, their titles and the defaults their fields start from, so the panel does
     * not carry a second copy of any of it.
     */
    #[OaListItemTypeAttribute(WatcherTypeResource::class)]
    public function types(): AnonymousResourceCollection
    {
        return WatcherTypeResource::collection(
            $this->findWatcherTypesByAdminUseCase->handle()
        );
    }

    public function delete(int $id): void
    {
        $this->deleteWatcherByAdminUseCase->handle($id);
    }
}
