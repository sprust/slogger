<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerRegistry;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Repositories\WatcherRepository;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One watcher's turn: look, record if there is something to record, tidy its line.
 *
 * Failures are caught here rather than raised. A pass covers every watcher, and one with
 * settings nothing can read is no reason to leave the rest unchecked — least of all in the
 * subsystem whose job is to notice trouble.
 */
readonly class CheckWatcherAction
{
    public function __construct(
        private WatcherCheckerRegistry $checkers,
        private RegisterTriggerAction $registerTriggerAction,
        private TrimWatcherTimelineAction $trimTimelineAction,
        private WatcherRepository $watcherRepository,
        private LoggerInterface $logger
    ) {
    }

    public function handle(WatcherObject $watcher, WatcherCheckContextObject $context): void
    {
        try {
            $trigger = $this->checkers->for($watcher->type)->check($watcher, $context);

            if (!is_null($trigger)) {
                $this->registerTriggerAction->handle($watcher, $trigger, $context->now);
            }

            $this->trimTimelineAction->handle($watcher, $context->now);

            // Last, and only on the way out without an error: this moment is the lower
            // bound of the next check's window for the watchers that read one, and moving
            // it past a check that did not happen would skip whatever was in between.
            $this->watcherRepository->updateCheckedAt($watcher->id, $context->now);
        } catch (Throwable $exception) {
            $this->logger->error("Watcher [$watcher->id] check failed: " . $exception->getMessage(), [
                'watcher'   => $watcher->id,
                'exception' => $exception,
            ]);
        }
    }
}
