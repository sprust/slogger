<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use SConcur\Laravel\Http\HttpServerRunner;

/**
 * Run a single SConcur HTTP server in the foreground: builds the server from
 * config('sconcur.http_server.server') and serves the Laravel handler in this
 * process. Used both standalone (dev) and as the master's worker script
 * (workerScript = artisan, workerArgs = [this command]).
 *
 * For a supervised, multi-worker setup use sconcur:servers:master:start.
 */
class HttpStartCommand extends AbstractSconcurCommand
{
    protected $signature = 'sconcur:servers:http:start
        {--masterPid= : Master pid, injected by the supervisor for orphan self-termination}';

    protected $description = 'Run a SConcur HTTP server in the foreground';

    public function handle(): int
    {
        $masterPid = $this->option('masterPid');

        new HttpServerRunner(
            masterPid: $masterPid !== null ? (int) $masterPid : null,
        )->run($this->getLaravel());

        return self::SUCCESS;
    }
}
