<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Domain\Events\WatcherIncidentChangedEvent;
use App\Modules\Watcher\Domain\Exceptions\WatcherIncidentNotFoundException;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Support\Carbon;

/**
 * A person says the incident is dealt with.
 *
 * Nothing closes an incident on its own. A watcher going quiet means the symptom stopped,
 * not that the cause was found, and closing on that would hide the only record that it
 * ever happened.
 */
readonly class CloseIncidentAction
{
    public function __construct(private WatcherIncidentRepository $incidentRepository, private DispatcherInterface $dispatcher)
    {
    }

    /**
     * @throws WatcherIncidentNotFoundException
     */
    public function handle(string $incidentId, ?int $closedByUserId): void
    {
        $incident = $this->incidentRepository->findById($incidentId);

        if (is_null($incident)) {
            throw new WatcherIncidentNotFoundException($incidentId);
        }

        // Already closed: whoever closed it first stays on the row. Two people pressing
        // the button is not an error worth showing either of them.
        if ($incident->status === WatcherIncidentStatusEnum::Closed) {
            return;
        }

        $this->incidentRepository->updateStatus(
            id: $incidentId,
            status: WatcherIncidentStatusEnum::Closed,
            closedAt: Carbon::now(),
            closedByUserId: $closedByUserId,
        );

        $changed = $this->incidentRepository->findById($incidentId);

        if (!is_null($changed)) {
            $this->dispatcher->event(new WatcherIncidentChangedEvent($changed));
        }
    }
}
