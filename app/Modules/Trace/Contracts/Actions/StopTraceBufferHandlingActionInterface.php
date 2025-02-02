<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Actions;

use Symfony\Component\Console\Output\OutputInterface;

interface StopTraceBufferHandlingActionInterface
{
    public function handle(OutputInterface $output): void;
}
