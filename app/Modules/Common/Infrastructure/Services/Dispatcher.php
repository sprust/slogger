<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;
use Throwable;
use App\Modules\Common\Infrastructure\Exceptions\ReportableException;

class Dispatcher implements DispatcherInterface
{
    /**
     * @var array<class-string<ShouldQueue>, true>
     */
    private array $jobClasses = [];

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly BusDispatcher $busDispatcher,
        private readonly ExceptionHandler $exceptionHandler,
        private readonly QueueRoutePolicy $queueRoutePolicy,
        private readonly LoggerInterface $logger
    ) {
    }

    public function event(object $event): void
    {
        $this->eventDispatcher->dispatch(
            event: $event,
        );
    }

    public function dispatch(ShouldQueue $job): void
    {
        if (!array_key_exists($job::class, $this->jobClasses)) {
            $this->logger->error(
                message: 'Application job is not registered.',
                context: [
                    'job_class' => $job::class,
                ],
            );

            return;
        }

        $queueRouteObject = $this->queueRoutePolicy->resolve($job);

        if (!$queueRouteObject->allowed) {
            // Reporting may dispatch another job, so refusals go straight to the logger.
            $this->logger->error(
                message: 'Application job queue route is not allowed.',
                context: [
                    'job_class'  => $job::class,
                    'connection' => substr($queueRouteObject->connection, 0, 100),
                    'queue'      => substr($queueRouteObject->queue, 0, 100),
                ],
            );

            return;
        }

        $this->busDispatcher->dispatch($job);
    }

    /**
     * @throws Throwable
     */
    public function report(Throwable $exception): void
    {
        $this->exceptionHandler->report(
            ReportableException::create($exception)
        );
    }

    public function registerJobs(array $jobClasses): void
    {
        foreach ($jobClasses as $jobClass) {
            $this->jobClasses[$jobClass] = true;
        }
    }
}
