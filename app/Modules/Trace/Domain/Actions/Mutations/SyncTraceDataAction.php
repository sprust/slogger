<?php

namespace App\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Interfaces\Mutations\SyncTraceDataActionInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceDataRepositoryInterface;
use Illuminate\Support\Arr;

readonly class SyncTraceDataAction implements SyncTraceDataActionInterface
{
    public function __construct(
        private TraceDataRepositoryInterface $traceDataRepository
    ) {
    }

    public function handle(array $data): array
    {
        $preparedData = Arr::dot($data);

        $keys = array_keys($data);

        foreach ($preparedData as $key => $value) {
            $keys[] = "$key | $value";
        }

        return $keys;

        $this->traceDataRepository->syncMany();
    }
}
