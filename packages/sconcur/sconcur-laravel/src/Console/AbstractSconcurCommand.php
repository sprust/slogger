<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use Illuminate\Console\Command;
use SConcur\Worker\MasterConfig;

/**
 * Shared base for the SConcur artisan commands.
 *
 * The master/HTTP configuration is taken from config('sconcur.http_server') and
 * turned into a MasterConfig in-process — no JSON file path is passed around.
 */
abstract class AbstractSconcurCommand extends Command
{
    /** Build the master config from the Laravel config (no JSON file). */
    protected function masterConfig(): MasterConfig
    {
        $config = (array) config('sconcur.http_server', []);

        // The master only supervises; per-request server tuning is read by the
        // worker (HttpServerRunner) from config, not injected via worker argv.
        unset($config['server']);

        return MasterConfig::fromArray($config);
    }
}
