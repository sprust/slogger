<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Actions\Mutations\CloseIncidentAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentStatAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentsAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentEventsRequest;
use App\Modules\Watcher\Infrastructure\Http\Requests\IndexIncidentsRequest;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentEventResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentResource;
use App\Modules\Watcher\Infrastructure\Http\Resources\WatcherIncidentStatResource;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

/**
 * A controller of its own, because an incident is not a watcher: it has its own list, its
 * own history and its own one action.
 */
readonly class WatcherIncidentController
{
    public function __construct(
        private FindIncidentsAction $findIncidentsAction,
        private FindIncidentEventsAction $findIncidentEventsAction,
        private FindIncidentStatAction $findIncidentStatAction,
        private CloseIncidentAction $closeIncidentAction,
        private FindUserByTokenAction $findUserByTokenAction
    ) {
    }

    #[OaListItemTypeAttribute(WatcherIncidentResource::class)]
    public function index(IndexIncidentsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $status = ArrayValueGetter::stringNull($validated, 'status');

        return WatcherIncidentResource::collection(
            $this->findIncidentsAction->handle(
                new FindIncidentsParameters(
                    status: $status ? WatcherIncidentStatusEnum::from($status) : null,
                    watcherId: ArrayValueGetter::intNull($validated, 'watcher_id'),
                    page: ArrayValueGetter::intNull($validated, 'page') ?? 1,
                    perPage: ArrayValueGetter::intNull($validated, 'per_page') ?? 50
                )
            )
        );
    }

    /**
     * How much there is to deal with, for the badge the header shows on every page.
     */
    public function stat(): WatcherIncidentStatResource
    {
        return new WatcherIncidentStatResource($this->findIncidentStatAction->handle());
    }

    #[OaListItemTypeAttribute(WatcherIncidentEventResource::class)]
    public function events(string $id, IndexIncidentEventsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        return WatcherIncidentEventResource::collection(
            $this->findIncidentEventsAction->handle(
                incidentId: $id,
                page: ArrayValueGetter::intNull($validated, 'page') ?? 1,
                perPage: ArrayValueGetter::intNull($validated, 'per_page') ?? 50
            )
        );
    }

    public function close(string $id, Request $request): void
    {
        try {
            $this->closeIncidentAction->handle($id, $this->currentUserId($request));
        } catch (WatcherIncidentNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        }
    }

    /**
     * Who is closing it, resolved from the token rather than from the request's user
     * resolver: what the middleware puts there is this application's own object, not
     * something the framework's Authenticatable contract describes.
     */
    private function currentUserId(Request $request): ?int
    {
        $token = $request->bearerToken();

        return $token ? $this->findUserByTokenAction->handle($token)?->id : null;
    }
}
