<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use SConcur\Laravel\Servers\MasterRunner;

/**
 * Trigger a rolling restart of the master's workers.
 */
class MasterReloadCommand extends AbstractSconcurCommand
{
    protected $signature = 'sconcur:servers:master:reload';

    protected $description = 'Reload the SConcur master supervisor';

    public function handle(): int
    {
        return new MasterRunner()->reload($this->masterConfig());
    }
}
