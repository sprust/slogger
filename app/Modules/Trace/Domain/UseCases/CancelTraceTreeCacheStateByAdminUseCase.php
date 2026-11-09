<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\UseCases;

use App\Modules\Trace\Domain\Exceptions\TraceTreeStateNotFoundException;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Domain\Actions\Mutations\CancelTraceTreeCacheStateAction;

readonly class CancelTraceTreeCacheStateByAdminUseCase
{
    public function __construct(
        private CancelTraceTreeCacheStateAction $cancelTraceTreeCacheStateAction
    ) {
    }

    /**
     * @throws TraceTreeStateNotFoundException
     */
    public function handle(string $rootTraceId): TraceTreeCacheStateObject
    {
        return $this->cancelTraceTreeCacheStateAction->handle(
            rootTraceId: $rootTraceId,
        ) ?? throw new TraceTreeStateNotFoundException(
            message: 'State not found',
        );
    }
}
