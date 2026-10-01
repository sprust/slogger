<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Trace\Domain\Actions\Mutations\DeletePartitionsAction;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

readonly class ClearTracesAction
{
    // a run takes seconds; one still open after this long died before it could close itself
    private const int STALE_PROCESS_MINUTES = 60;

    public function __construct(
        private ProcessRepository $processRepository,
        private DeletePartitionsAction $deletePartitionsAction,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function handle(int $lifetimeHours): void
    {
        if ($lifetimeHours <= 0) {
            throw new InvalidArgumentException(
                'Lifetime hours must be greater than 0'
            );
        }

        $this->closeStaleProcess();

        $loggedAtTo = Carbon::now()->clone()->subHours($lifetimeHours);

        $process = $this->processRepository->create();

        $deletedTraces = $this->deletePartitionsAction->handle(
            loggedAtTo: $loggedAtTo
        );

        if (
            $deletedTraces->exception === null &&
            $deletedTraces->partitionsCount === 0 &&
            $deletedTraces->tracesCount === 0
        ) {
            $this->processRepository->deleteByProcessId(
                processId: $process->id
            );

            return;
        }

        $this->processRepository->update(
            processId: $process->id,
            // the column still says collections: the page shows it as the count of what was dropped
            clearedCollectionsCount: $deletedTraces->partitionsCount,
            clearedTracesCount: $deletedTraces->tracesCount,
            clearedAt: Carbon::now(),
            exception: $deletedTraces->exception
        );
    }

    /**
     * A run that crashed before it closed itself would block every later one: once it is
     * old enough it is closed with an error, while a fresh one still blocks.
     *
     * @throws RuntimeException
     */
    private function closeStaleProcess(): void
    {
        $process = $this->processRepository->exists(
            clearedAtIsNull: true
        );

        if ($process === null) {
            return;
        }

        if ($process->createdAt->gt(Carbon::now()->subMinutes(self::STALE_PROCESS_MINUTES))) {
            throw new RuntimeException(
                'Clearing process already active'
            );
        }

        $this->processRepository->update(
            processId: $process->id,
            clearedCollectionsCount: $process->clearedCollectionsCount,
            clearedTracesCount: $process->clearedTracesCount,
            clearedAt: Carbon::now(),
            exception: new RuntimeException(
                sprintf('The run did not finish within %d minutes', self::STALE_PROCESS_MINUTES)
            )
        );
    }
}
