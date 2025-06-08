<?php

namespace App\Console\Commands\Octane\Roadrunner;

use Laravel\Octane\RoadRunner\ServerProcessInspector;
use Laravel\Octane\RoadRunner\ServerStateFile;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class StartRoadRunnerCommand extends \Laravel\Octane\Commands\StartRoadRunnerCommand
{
    public $signature = 'octane:roadrunner
                    {--host= : The IP address the server should bind to}
                    {--port= : The port the server should be available on}
                    {--rpc-host= : The RPC IP address the server should bind to}
                    {--rpc-port= : The RPC port the server should be available on}

                    {--grpc-host= : The GRPC IP address the server should bind to}
                    {--grpc-port= : The GRPC port the server should be available on}
                    {--grpc-workers= : The number of workers that should be available to handle requests}
                    {--grpc-max-requests=500 : The number of requests to process before reloading the server}

                    {--workers=auto : The number of workers that should be available to handle requests}
                    {--max-requests=500 : The number of requests to process before reloading the server}
                    {--rr-config= : The path to the RoadRunner .rr.yaml file}
                    {--watch : Automatically reload the server when the application is modified}
                    {--poll : Use file system polling while watching in order to watch files over a network}
                    {--log-level= : Log messages at or above the specified log level}';

    public function handle(ServerProcessInspector $inspector, ServerStateFile $serverStateFile): int
    {
        if (!$this->isRoadRunnerInstalled()) {
            $this->error('RoadRunner not installed. Please execute the `octane:install` Artisan command.');

            return 1;
        }

        $roadRunnerBinary = $this->ensureRoadRunnerBinaryIsInstalled();

        $this->ensurePortIsAvailable();

        if ($inspector->serverIsRunning()) {
            $this->error('RoadRunner server is already running.');

            return 1;
        }

        $this->ensureRoadRunnerBinaryMeetsRequirements($roadRunnerBinary);

        $this->writeServerStateFile($serverStateFile);

        $this->forgetEnvironmentVariables();

        $options = [
            'version=3',
            'http.address=' . $this->option('host') . ':' . $this->getPort(),
            'server.command=' . (new PhpExecutableFinder)->find() . ',' . base_path(
                config('octane.roadrunner.command', 'vendor/bin/roadrunner-worker')
            ),
            'http.pool.num_workers=' . $this->workerCount(),
            'http.pool.max_jobs=' . $this->option('max-requests'),
            'rpc.listen=tcp://' . $this->rpcHost() . ':' . $this->rpcPort(),

            // Thx Taylor for good code
            'grpc.listen=tcp://' . $this->grpcHost() . ':' . $this->grpcPort(),
            'grpc.proto=' . base_path('packages/slogger/grpc/proto/*.proto'),
            'grpc.pool.command=' . base_path('grpc/rr-grpc-worker.php'),
            'grpc.pool.num_workers=' . $this->option('grpc-workers'),
            'grpc.pool.max_jobs=' . $this->option('grpc-max-requests'),

            'http.pool.supervisor.exec_ttl=' . $this->maxExecutionTime(),
            'http.static.dir=' . public_path(),
            'http.middleware=' . config('octane.roadrunner.http_middleware', 'static'),
            'logs.mode=production',
            'logs.level=' . ($this->option('log-level') ?: (app()->environment('local') ? 'debug' : 'warn')),
            'logs.output=stdout',
            'logs.encoding=json',
        ];

        $commandOptions = [];

        while ($option = array_shift($options)) {
            $commandOptions[] = '-o';
            $commandOptions[] = $option;
        }

        unset($options);

        $command = array_filter([
            $roadRunnerBinary,
            '-c',
            $this->configPath(),
            ...$commandOptions,
            'serve',
        ]);

        $process = new Process($command, base_path(), [
            'APP_ENV'        => app()->environment(),
            'APP_BASE_PATH'  => base_path(),
            'LARAVEL_OCTANE' => 1,
        ]);

        $process->start();

        $serverStateFile->writeProcessId($process->getPid());

        return $this->runServer($process, $inspector, 'roadrunner');
    }

    protected function grpcHost(): string
    {
        return $this->option('grpc-host') ?: $this->getHost();
    }

    protected function grpcPort(): int
    {
        return ((int) $this->option('grpc-port')) ?: ((int) $this->getPort()) - 2999;
    }
}
