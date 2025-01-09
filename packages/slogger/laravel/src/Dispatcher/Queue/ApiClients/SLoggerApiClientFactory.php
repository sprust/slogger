<?php

namespace SLoggerLaravel\Dispatcher\Queue\ApiClients;

use Exception;
use Grpc\ChannelCredentials;
use GuzzleHttp\Client;
use RuntimeException;
use SLoggerGrpc\Services\SLoggerTraceCollectorGrpcService;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\Grpc\SLoggerGrpcClient;
use SLoggerLaravel\Dispatcher\Queue\ApiClients\Http\SLoggerHttpClient;

readonly class SLoggerApiClientFactory
{
    private string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('slogger.token');
    }

    public function create(string $apiClientName): SLoggerApiClientInterface
    {
        return match ($apiClientName) {
            'http' => $this->createHttp(),
            'grpc' => $this->createGrpc(),
            default => throw new RuntimeException("Unknown api client [$apiClientName]"),
        };
    }

    private function createHttp(): SLoggerHttpClient
    {
        $url = config('slogger.dispatchers.queue.api_clients.http.url');

        return new SLoggerHttpClient(
            new Client([
                'headers'  => [
                    'Authorization'    => "Bearer $this->apiToken",
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Content-Type'     => 'application/json',
                    'Accept'           => 'application/json',
                ],
                'base_uri' => $url,
            ])
        );
    }

    private function createGrpc(): SLoggerGrpcClient
    {
        if (!class_exists(SLoggerTraceCollectorGrpcService::class)) {
            throw new RuntimeException(
                'The package slogger/grpc is not installed'
            );
        }

        $url = config('slogger.dispatchers.queue.api_clients.grpc.url');

        try {
            return new SLoggerGrpcClient(
                apiToken: $this->apiToken,
                grpcService: new SLoggerTraceCollectorGrpcService(
                    hostname: $url,
                    opts: [
                        'credentials' => ChannelCredentials::createInsecure(),
                    ]
                )
            );
        } catch (Exception $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }
}
