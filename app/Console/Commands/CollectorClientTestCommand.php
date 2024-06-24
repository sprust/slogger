<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SLoggerLaravel\ApiClients\SLoggerApiClientFactory;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;

// TODO: delete after tests
class CollectorClientTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace:collector:test {client : http, grpc} {requestsCount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Local command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $clientName = $this->argument('client');

        $this->warn($clientName);

        $client = app(SLoggerApiClientFactory::class)->create($clientName);

        $requestsCount = $this->argument('requestsCount');

        $start = microtime(true);

        $times = [];

        while ($requestsCount--) {
            $startRequest = microtime(true);

            $traces = new SLoggerTraceObjects();

            $traces->add(
                new SLoggerTraceObject(
                    traceId: Str::uuid()->toString(),
                    parentTraceId: null,
                    type: 'test',
                    status: 'success',
                    tags: [],
                    data: [],
                    duration: 0.1,
                    memory: 0.2,
                    cpu: 0.3,
                    loggedAt: now('UTC'),
                )
            );

            $client->sendTraces($traces);

            $times[] = microtime(true) - $startRequest;
        }

        $this->info('total: ' . microtime(true) - $start);
        $this->info('avg: ' . collect($times)->avg());

        return self::SUCCESS;
    }
}
