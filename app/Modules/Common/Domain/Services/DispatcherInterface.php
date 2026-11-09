<?php

declare(strict_types=1);

namespace App\Modules\Common\Domain\Services;

use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

interface DispatcherInterface
{
    public function event(object $event): void;

    public function dispatch(ShouldQueue $job): void;

    public function report(Throwable $exception): void;

    /**
     * @param array<class-string<ShouldQueue>> $jobClasses
     */
    public function registerJobs(array $jobClasses): void;
}
