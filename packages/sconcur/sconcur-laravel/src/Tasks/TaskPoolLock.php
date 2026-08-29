<?php

declare(strict_types=1);

namespace SConcur\Laravel\Tasks;

/**
 * Keeps a second pool from running beside the first.
 *
 * It matters more than it looks: cron used to guard itself with a cache key that every
 * start overwrote, so a hand-started copy quietly took over from the supervised one.
 * Here a second pool does not start at all, and `schedule:run` cannot fire twice a
 * minute.
 *
 * flock rather than a cache lock, for the same reason the library's master uses it: the
 * kernel drops the lock when the process dies, SIGKILL included, so there is no stale
 * lock to reason about and no TTL to keep refreshing.
 */
class TaskPoolLock
{
    /** @var resource|null */
    protected mixed $handle = null;

    public function __construct(protected string $path)
    {
    }

    public function acquire(): bool
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            return false;
        }

        $handle = fopen($this->path, 'c');

        if ($handle === false) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }

        $this->handle = $handle;

        return true;
    }

    public function release(): void
    {
        if ($this->handle === null) {
            return;
        }

        flock($this->handle, LOCK_UN);
        fclose($this->handle);

        $this->handle = null;
    }

    public function path(): string
    {
        return $this->path;
    }
}
