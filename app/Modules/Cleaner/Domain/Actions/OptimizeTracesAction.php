<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Trace\Domain\Actions\Mutations\OptimizePartitionsAction;
use Illuminate\Support\Carbon;

/**
 * Merges the hours of traces that closed more than an hour ago into one part each.
 *
 * An hour waits one more before it is merged: the updates of long requests and jobs still
 * reach it for a while, and each of them would leave it in two parts again. A partition
 * that does get late traces is merged again on the next run.
 */
readonly class OptimizeTracesAction
{
    public function __construct(
        private OptimizePartitionsAction $optimizePartitionsAction,
    ) {
    }

    public function handle(): int
    {
        return $this->optimizePartitionsAction->handle(
            Carbon::now('UTC')->subHour()
        );
    }
}
