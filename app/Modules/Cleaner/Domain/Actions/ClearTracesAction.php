<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Trace\Domain\Actions\Mutations\DeletePartitionsAction;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

readonly class ClearTracesAction
{
    public function __construct(
        private ProcessRepository $processRepository,
        private DeletePartitionsAction $deletePartitionsAction,
    ) {
    }

    public function handle(int $lifetimeHours): void
    {
        if ($lifetimeHours <= 0) {
            throw new InvalidArgumentException(
                'Lifetime hours must be greater than 0'
            );
        }

        $exists = $this->processRepository->exists(
            clearedAtIsNull: true
        );

        if ($exists) {
            throw new RuntimeException(
                'Clearing process already active'
            );
        }

        $loggedAtTo = Carbon::now()->clone()->subHours($lifetimeHours);

        $process = $this->processRepository->create();

        $deletedTraces = null;
        $exception     = null;

        try {
            $deletedTraces = $this->deletePartitionsAction->handle(
                loggedAtTo: $loggedAtTo
            );
        } catch (Throwable $exception) {
            //
        }

        if (
            $exception === null &&
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
            clearedCollectionsCount: $deletedTraces?->partitionsCount ?: 0,
            clearedTracesCount: $deletedTraces?->tracesCount ?: 0,
            clearedAt: Carbon::now(),
            exception: $exception
        );
    }
}
