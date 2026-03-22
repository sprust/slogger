<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\ClearTracesActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;
use App\Modules\Trace\Contracts\Actions\Mutations\DeleteCollectionsActionInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

readonly class ClearTracesAction implements ClearTracesActionInterface
{
    public function __construct(
        private ProcessRepositoryInterface $processRepository,
        private DeleteCollectionsActionInterface $deleteCollectionsAction,
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

        $loggedAtTo = now()->clone()->subDays($lifetimeDays);

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

        $this->processRepository->update(
            processId: $process->id,
            clearedCollectionsCount: $deletedTraces?->collectionsCount ?: 0,
            clearedTracesCount: $deletedTraces?->tracesCount ?: 0,
            clearedAt: now(),
            exception: $exception
        );
    }
}
