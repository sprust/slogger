<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use SConcur\Laravel\Servers\MasterRunner;

/**
 * Report status of the SConcur master supervisor.
 */
class MasterStatusCommand extends AbstractSconcurCommand
{
    protected $signature = 'sconcur:servers:master:status';

    protected $description = 'Show the SConcur master supervisor status';

    public function handle(): int
    {
        return new MasterRunner()->status($this->masterConfig());
    }
}
