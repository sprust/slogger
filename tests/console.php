<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tests\Support\TestingEnvironment;

define('LARAVEL_START', microtime(true));

require __DIR__ . '/bootstrap.php';

$application = require __DIR__ . '/../bootstrap/app.php';

TestingEnvironment::guard($application);

$kernel = $application->make(Kernel::class);
$input  = new ArgvInput();
$status = $kernel->handle(
    input: $input,
    output: new ConsoleOutput(),
);

$kernel->terminate(
    input: $input,
    status: $status,
);

exit($status);
