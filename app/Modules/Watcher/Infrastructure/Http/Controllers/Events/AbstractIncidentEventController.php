<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Controllers\Events;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentEventsAction;
use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

/**
 * What every watcher type's event controller does once the route has named the type.
 *
 * There is a controller per type because the payload follows the type: the stored
 * document does not carry it, and the generated schema can then say which numbers an
 * event of this watcher holds instead of offering every number any type might hold and
 * leaving the panel to guess which are filled.
 */
abstract readonly class AbstractIncidentEventController
{
    public function __construct(
        private FindIncidentAction $findIncidentAction,
        private FindWatcherAction $findWatcherAction,
        private FindIncidentEventsAction $findIncidentEventsAction
    ) {
    }

    /**
     * An incident of another type answers 404 rather than a page of empty payloads: the
     * route names a type, and that is the promise the answer keeps.
     *
     * @param array<string, mixed> $validated
     *
     * @return WatcherIncidentEventObject[]
     */
    protected function incidentEvents(WatcherTypeEnum $type, string $incidentId, array $validated): array
    {
        $incident = $this->findIncidentAction->handle($incidentId);

        if (is_null($incident)) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher incident [$incidentId] not found");
        }

        // Soft-deleted included: an incident outlives the watcher that found it, and the
        // row is kept precisely so that what it found still has a type behind it.
        $watcher = $this->findWatcherAction->handle($incident->watcherId, withTrashed: true);

        if (is_null($watcher) || $watcher->type !== $type) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Watcher incident [$incidentId] not found");
        }

        return $this->findIncidentEventsAction->handle(
            incidentId: $incidentId,
            type: $type,
            page: ArrayValueGetter::intNull($validated, 'page') ?? 1,
            perPage: ArrayValueGetter::intNull($validated, 'per_page') ?? 50
        );
    }
}
