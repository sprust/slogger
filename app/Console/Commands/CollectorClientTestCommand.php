<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SLoggerLaravel\ApiClients\SLoggerApiClientFactory;
use SLoggerLaravel\Objects\SLoggerTraceObject;
use SLoggerLaravel\Objects\SLoggerTraceObjects;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObject;
use SLoggerLaravel\Objects\SLoggerTraceUpdateObjects;

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
            $null = ($requestsCount % 2) === 0;

            $startRequest = microtime(true);

            $traces = new SLoggerTraceObjects();

            $traceId = Str::uuid()->toString();

            $traces->add(
                new SLoggerTraceObject(
                    traceId: $traceId,
                    parentTraceId: $null ? null : Str::uuid()->toString(),
                    type: 'test',
                    status: 'started',
                    tags: $null ? [] : [
                        'tag1',
                    ],
                    data: $null ? [] : [
                        'int' => 1,
                        'string' => '1',
                        'float' => 0.1,
                        'bool' => false,
                    ],
                    duration: $null ? null : 0.1,
                    memory: $null ? null : 0.2,
                    cpu: $null ? null : 0.3,
                    loggedAt: now('UTC'),
                )
            );

            $client->sendTraces($traces);

            if ($null) {
                continue;
            }

            $traces = new SLoggerTraceUpdateObjects();

            $traces->add(
                new SLoggerTraceUpdateObject(
                    traceId: $traceId,
                    status: 'success',
                    tags: [
                        'tag1-upd',
                    ],
                    data: [
                        'int-upd' => 1,
                        'string-upd' => '1',
                        'float-upd' => 0.1,
                        'bool-upd' => false,
                    ],
                    duration: 1.1,
                    memory: 1.2,
                    cpu: 1.3,
                )
            );

            $client->updateTraces($traces);

            $times[] = microtime(true) - $startRequest;
        }

        $this->info('total: ' . microtime(true) - $start);
        $this->info('avg: ' . collect($times)->avg());

        return self::SUCCESS;
    }
}
