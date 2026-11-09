<?php

declare(strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Providers\Fixtures;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use LogicException;
use Throwable;

class RecordingRegistrationDispatcher implements DispatcherInterface
{
    /** @var list<class-string<ShouldQueue>> */
    public array $jobs = [];

    public function registerJobs(array $jobClasses): void
    {
        array_push($this->jobs, ...$jobClasses);
    }

    public function event(object $event): void
    {
        throw new LogicException('Providers must not emit events during registration');
    }

    public function dispatch(ShouldQueue $job): void
    {
        throw new LogicException('Providers must not dispatch jobs during registration');
    }

    public function report(Throwable $exception): void
    {
        throw new LogicException('Providers must not report exceptions during registration');
    }
}
