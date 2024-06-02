<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Repositories\Interfaces\CollectorTraceRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\CollectorTraceTreeRepositoryInterface;

readonly class FreshTraceTreeAction
{
    public function __construct(
        private CollectorTraceRepositoryInterface $traceRepository,
        private CollectorTraceTreeRepositoryInterface      $traceTreeRepository
    ) {
    }

    public function handle(): void
    {
        $to = now();

        // TODO: to delete by batch in cycle below
        $this->traceTreeRepository->deleteMany(to: $to);

        $page = 1;

        while (true) {
            $trees = $this->traceRepository->findTree(
                page: $page,
                to: $to
            );

            if (!count($trees)) {
                break;
            }

            $this->traceTreeRepository->insertMany($trees);

            ++$page;
        }
    }
}
