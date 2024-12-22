<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

readonly class TraceFieldTitlesService
{
    /**
     * @var array<string, string>
     */
    private array $map;

    public function __construct()
    {
        $this->map = [
            'sid'  => 'serviceId',
            'tid'  => 'traceId',
            'ptid' => 'parentTraceId',
            'tp'   => 'type',
            'st'   => 'status',
            'tgs'  => 'tags',
            'dt'   => 'data',
            'dur'  => 'duration',
            'mem'  => 'memory',
            'cpu'  => 'cpu',
            'hpr'  => 'has profiling',
            'pr'   => 'profiling',
            'lat'  => 'loggedAt',
            'tss'  => 'timestamps',
            'cl'   => 'cleared',
            'cat'  => 'createdAt',
            'uat'  => 'updatedAt',
        ];
    }

    public function get(string $field): string
    {
        return $this->map[$field] ?? $field;
    }
}
