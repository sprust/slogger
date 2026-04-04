<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Throwable;

readonly class BuildTraceTreeCacheAction
{
    public function __construct(
        private TraceTreeCacheBuilderService $traceTreeCacheBuilderService,
        private TraceTreeCacheStateRepository $traceTreeCacheStateRepository,
    ) {
    }

    public function handle(string $rootTraceId, string $version): void
    {
        try {
            if (!$this->shouldContinue($rootTraceId, $version)) {
                return;
            }

            $completed = $this->traceTreeCacheBuilderService->handle(
                rootTraceId: $rootTraceId,
                version: $version,
                shouldContinue: fn(): bool => $this->shouldContinue($rootTraceId, $version),
            );

            if (!$completed) {
                return;
            }

            $this->traceTreeCacheStateRepository->markFinished(
                rootTraceId: $rootTraceId,
                version: $version,
            );
        } catch (Throwable $exception) {
            if (!$this->shouldContinue($rootTraceId, $version)) {
                return;
            }

            $this->traceTreeCacheStateRepository->markFailed(
                rootTraceId: $rootTraceId,
                version: $version,
                error: $exception::class . ': ' . ($exception->getMessage() ?: 'Unknown error'),
            );
        }
    }

    private function shouldContinue(string $rootTraceId, string $version): bool
    {
        $state = $this->traceTreeCacheStateRepository->findOneByRootTraceId($rootTraceId);

        return $state !== null
            && $state->status === TraceTreeCacheStateStatusEnum::InProcess
            && $state->version === $version;
    }
}
