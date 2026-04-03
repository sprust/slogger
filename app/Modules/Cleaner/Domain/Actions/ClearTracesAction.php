<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Trace\Domain\Actions\Mutations\DeleteCollectionsAction;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

readonly class ClearTracesAction
{
    public function __construct(
        private ProcessRepository $processRepository,
        private DeleteCollectionsAction $deleteCollectionsAction,
    ) {
    }

    public function handle(int $lifetimeDays): void
    {
        if ($lifetimeDays <= 0) {
            throw new InvalidArgumentException(
                'Lifetime days must be greater than 0'
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

        $loggedAtTo = Carbon::now()->clone()->subDays($lifetimeDays);

        $process = $this->processRepository->create();

        $deletedTraces = null;
        $exception     = null;

        try {
            $deletedTraces = $this->deleteCollectionsAction->handle(
                loggedAtTo: $loggedAtTo
            );
        } catch (Throwable $exception) {
            //
        }

        if (
            $exception === null &&
            $deletedTraces->collectionsCount === 0 &&
            $deletedTraces->tracesCount === 0
        ) {
            $this->processRepository->deleteByProcessId(
                processId: $process->id
            );

            return;
        }

        $this->processRepository->update(
            processId: $process->id,
            clearedCollectionsCount: $deletedTraces?->collectionsCount ?: 0,
            clearedTracesCount: $deletedTraces?->tracesCount ?: 0,
            clearedAt: Carbon::now(),
            exception: $exception
        );
    }
}
