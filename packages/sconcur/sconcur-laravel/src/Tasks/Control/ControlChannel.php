<?php

declare(strict_types=1);

namespace SConcur\Laravel\Tasks\Control;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * How a command reaches a running pool from another process.
 *
 * Signals cannot do this job: stopping one task from php-fpm or from `make art` means
 * finding the pool's pid first. A cache key is reachable from anywhere the application
 * runs, which is exactly what the cron and monitor stop commands used it for before.
 */
readonly class ControlChannel
{
    public function __construct(
        protected CacheRepository $cache,
        protected string $key,
    ) {
    }

    public function send(ControlActionEnum $action, string $target = ControlCommandDto::ALL): ControlCommandDto
    {
        $command = new ControlCommandDto(
            action: $action,
            target: $target === '' ? ControlCommandDto::ALL : $target,
            at: microtime(true),
        );

        $this->cache->forever($this->key, $command->toArray());

        return $command;
    }

    /**
     * Takes the pending command if it is newer than $notBefore, and clears the key
     * either way.
     *
     * Both halves matter. Clearing keeps a command from being replayed; the timestamp
     * check is what actually holds when clearing does not happen — a pool killed with
     * SIGKILL between reading and clearing would otherwise come back up, find its own
     * stop still sitting there and stop again, for as long as the supervisor kept
     * restarting it.
     */
    public function take(float $notBefore): ?ControlCommandDto
    {
        $data = $this->cache->get($this->key);

        if (!is_array($data)) {
            return null;
        }

        $this->cache->forget($this->key);

        $command = ControlCommandDto::fromArray($data);

        if ($command === null || $command->at <= $notBefore) {
            return null;
        }

        return $command;
    }
}
