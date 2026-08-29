<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use Illuminate\Console\Command;
use RuntimeException;
use SConcur\Worker\MasterConfig;

/**
 * Shared base for the SConcur artisan commands.
 *
 * The master configuration is taken from config('sconcur.http_server') and turned
 * into a MasterConfig in-process, so start/stop/status need no JSON file at all.
 *
 * Reload is the exception: the master re-reads the config from disk to pick the
 * edit up, so it takes a path rather than an in-memory object. masterConfigPath()
 * materializes the very same array the other commands use, which keeps the file
 * the master reloads from and the config this process supervises with in step.
 */
abstract class AbstractSconcurCommand extends Command
{
    /** Build the master config from the Laravel config (no JSON file). */
    protected function masterConfig(): MasterConfig
    {
        return MasterConfig::fromArray($this->masterConfigArray());
    }

    /**
     * The master config as the library reads it.
     *
     * `server` is dropped: the master forwards a group's server block to its
     * workers' argv, and the worker here is artisan, which fails on flags its
     * command does not declare. Per-request server tuning is read by the worker
     * (HttpServerRunner) from config instead.
     *
     * @return array<string, mixed>
     */
    protected function masterConfigArray(): array
    {
        $config = (array) config('sconcur.http_server', []);

        unset($config['server']);

        foreach ($config['groups'] ?? [] as $index => $group) {
            unset($config['groups'][$index]['server']);
        }

        return $config;
    }

    /**
     * The --group option as the library wants it: a group name, or '' for every
     * group. Console options are typed array|bool|string|null, and only a string
     * names a group.
     */
    protected function groupOption(): string
    {
        $group = $this->option('group');

        return is_string($group) ? $group : '';
    }

    /**
     * Write the master config to disk and return its absolute path.
     *
     * Only reload needs this: the master reads the file in its own process, so an
     * in-memory config cannot reach it. The file goes next to the master's lock and
     * state, under the runtimeDir it already owns.
     */
    protected function masterConfigPath(): string
    {
        $config = $this->masterConfigArray();

        $runtimeDir = (string) ($config['runtimeDir'] ?? '');

        if ($runtimeDir === '') {
            throw new RuntimeException('sconcur: config("sconcur.http_server.runtimeDir") is empty');
        }

        if (!is_dir($runtimeDir) && !mkdir($runtimeDir, 0o775, true) && !is_dir($runtimeDir)) {
            throw new RuntimeException('sconcur: cannot create the runtime dir: ' . $runtimeDir);
        }

        $path = $runtimeDir . '/' . ($config['name'] ?? 'sconcur-server') . '.config.json';

        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false || file_put_contents($path, $json . PHP_EOL) === false) {
            throw new RuntimeException('sconcur: cannot write the master config: ' . $path);
        }

        return $path;
    }
}
