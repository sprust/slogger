<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Contracts\Actions\Mutations\FreshTraceTimestampsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;

readonly class FreshTraceTimestampsAction implements FreshTraceTimestampsActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private MakeTraceTimestampsActionInterface $makeTraceTimestampsAction
    ) {
    }

    public function handle(): void
    {
        $to = now('UTC');

        $page = 1;

        while (true) {
            $traceLoggedAtList = $this->traceRepository->findLoggedAtList(
                page: $page,
                perPage: 1000,
                loggedAtTo: $to
            );

            if (empty($traceLoggedAtList)) {
                break;
            }

            ++$page;

            foreach ($traceLoggedAtList as $traceLoggedAt) {
                $this->traceRepository->updateTraceTimestamps(
                    traceId: $traceLoggedAt->traceId,
                    timestamps: $this->makeTraceTimestampsAction->handle(
                        date: $traceLoggedAt->loggedAt
                    )
                );
            }
        }
    }
}
