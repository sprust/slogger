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

        // Appended rather than written over. Commands are taken on the pool's poll
        // interval, so two sent inside one of those windows — `stop --task=cron` then
        // `stop --task=indexes`, or a script issuing both — would otherwise leave only
        // the second, and the first would be silently lost.
        $pending   = $this->pending();
        $pending[] = $command->toArray();

        $this->cache->forever($this->key, $pending);

        return $command;
    }

    /**
     * Takes every pending command newer than $notBefore, in the order they were sent,
     * and clears the key either way.
     *
     * Both halves matter. Clearing keeps a command from being replayed; the timestamp
     * check is what actually holds when clearing does not happen — a pool killed with
     * SIGKILL between reading and clearing would otherwise come back up, find its own
     * stop still sitting there and stop again, for as long as the supervisor kept
     * restarting it.
     *
     * @return list<ControlCommandDto>
     */
    public function takeAll(float $notBefore): array
    {
        $pending = $this->pending();

        if ($pending === []) {
            return [];
        }

        $this->cache->forget($this->key);

        $commands = [];

        foreach ($pending as $data) {
            $command = is_array($data) ? ControlCommandDto::fromArray($data) : null;

            if ($command === null || $command->at <= $notBefore) {
                continue;
            }

            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * What is in the key right now, as a list of raw commands.
     *
     * A single command written by an older build is read as a list of one, so a pool and
     * a control command from either side of a deploy still understand each other.
     *
     * @return list<mixed>
     */
    protected function pending(): array
    {
        $data = $this->cache->get($this->key);

        if (!is_array($data)) {
            return [];
        }

        return array_is_list($data) ? $data : [$data];
    }
}
