<?php

namespace Tests\Services\Tasks;

use SConcur\Laravel\Tasks\TaskPoolLogger;

/** The pool's logger writes to stdout; a test has no use for that. */
class SilentLogger extends TaskPoolLogger
{
    public function log(string $scope, string $message): void
    {
    }
}
