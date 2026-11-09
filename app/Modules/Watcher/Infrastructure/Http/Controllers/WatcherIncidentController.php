<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Watcher\Parameters\CloseIncidentByAdminParameters;
use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\UseCases\CloseIncidentByAdminUseCase;
use App\Modules\Watcher\Domain\UseCases\FindIncidentStatByAdminUseCase;
use App\Modules\Watcher\Domain\UseCases\FindIncidentsByAdminUseCase;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentStatResource;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

/**
 * A controller of its own, because an incident is not a watcher: it has its own list, its
 * own history and its own one action.
 */
readonly class WatcherIncidentController
{
    public function __construct(
        private FindIncidentsByAdminUseCase $findIncidentsByAdminUseCase,
        private FindIncidentStatByAdminUseCase $findIncidentStatByAdminUseCase,
        private CloseIncidentByAdminUseCase $closeIncidentByAdminUseCase
    ) {
    }

    #[OaListItemTypeAttribute(WatcherIncidentResource::class)]
    public function index(
        IndexIncidentsRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();
        $status    = ArrayValueGetter::stringNull(
            data: $validated,
            key: 'status',
        );

        return WatcherIncidentResource::collection(
            $this->findIncidentsByAdminUseCase->handle(
                new FindIncidentsParameters(
                    status: $status ? WatcherIncidentStatusEnum::from($status) : null,
                    watcherId: ArrayValueGetter::intNull(
                        data: $validated,
                        key: 'watcher_id',
                    ),
                    page: ArrayValueGetter::intNull(
                        data: $validated,
                        key: 'page',
                    ) ?? 1,
                    perPage: ArrayValueGetter::intNull(
                        data: $validated,
                        key: 'per_page',
                    ) ?? 50,
                )
            )
        );
    }

    /**
     * How much there is to deal with, for the badge the header shows on every page.
     */
    public function stat(): WatcherIncidentStatResource
    {
        return new WatcherIncidentStatResource(
            $this->findIncidentStatByAdminUseCase->handle()
        );
    }

    public function close(string $id, Request $request): void
    {
        $this->closeIncidentByAdminUseCase->handle(
            new CloseIncidentByAdminParameters(
                incidentId: $id,
                token: $request->bearerToken(),
            )
        );
    }
}
