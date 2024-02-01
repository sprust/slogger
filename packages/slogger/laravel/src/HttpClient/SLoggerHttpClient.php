<?php

namespace SLoggerLaravel\HttpClient;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

class SLoggerHttpClient
{
    public function __construct(protected ClientInterface $client)
    {
    }

    /**
     * @throws GuzzleException
     */
    public function sendTraces(SLoggerTraceObjects $traceObjects): void
    {
        $traces = [];

        foreach ($traceObjects->get() as $traceObject) {
            $traces[] = [
                'trace_id'        => $traceObject->traceId,
                'parent_trace_id' => $traceObject->parentTraceId,
                'type'            => $traceObject->type,
                'tags'            => $traceObject->tags,
                'data'            => json_encode($traceObject->data),
                'logged_at'       => (float) ($traceObject->loggedAt->unix()
                    . '.' . $traceObject->loggedAt->microsecond),
            ];
        }

        $this->client->request('post', '/traces-api', [
            'json' => [
                'traces' => $traces,
            ],
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function updateTraces(SLoggerTraceUpdateObjects $traceObjects): void
    {
        $traces = [];

        foreach ($traceObjects->get() as $traceObject) {
            $traces[] = [
                'trace_id' => $traceObject->traceId,
                'tags'     => $traceObject->tags,
                'data'     => json_encode($traceObject->data),
            ];
        }

        $this->client->request('patch', '/traces-api', [
            'json' => [
                'traces' => $traces,
            ],
        ]);
    }
}