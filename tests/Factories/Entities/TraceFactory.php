<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;

readonly class TraceFactory
{
    public function __construct(
        private ClickhouseClient $clickhouseClient,
        private ServiceFactory $serviceFactory
    ) {
    }

    /** @throws ClickhouseQueryException */
    public function create(
        ?int $serviceId = null,
        ?string $traceId = null,
        ?string $parentTraceId = null,
        ?string $type = null,
        ?string $status = null,
        ?Carbon $loggedAt = null,
    ): string {
        $traceId  = $traceId ?? fake()->uuid();
        $loggedAt = $loggedAt ?? Carbon::now();

        // Trace ingestion belongs to Go; PHP repositories intentionally expose only reads.
        $this->clickhouseClient->command(
            sql: <<<'SQL'
                INSERT INTO traces (sid, tid, ptid, tp, st, dt_raw, lat, cat, uat)
                VALUES ({serviceId:UInt32}, {traceId:String}, {parentTraceId:String},
                    {type:String}, {status:String}, '{}', {loggedAt:DateTime64(6, 'UTC')},
                    {loggedAt:DateTime64(6, 'UTC')}, {loggedAt:DateTime64(6, 'UTC')})
                SQL,
            params: [
                'serviceId'     => $serviceId ?? $this->serviceFactory->create(),
                'traceId'       => $traceId,
                'parentTraceId' => $parentTraceId ?? '',
                'type'          => $type ?? fake()->word(),
                'status'        => $status ?? fake()->word(),
                'loggedAt'      => $loggedAt,
            ],
        );

        return $traceId;
    }
}
