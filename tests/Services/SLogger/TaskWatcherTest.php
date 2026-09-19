<?php

declare(strict_types=1);

namespace Tests\Services\SLogger;

use App\Services\SLogger\TaskWatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Laravel\Tasks\Events\TaskTickFinished;
use SConcur\Laravel\Tasks\Events\TaskTickStarted;
use SConcur\Laravel\Tasks\TickResultEnum;
use SLoggerLaravel\Context\ArrayTraceContext;
use SLoggerLaravel\Enums\TraceStatusEnum;
use SLoggerLaravel\Processor;

class TaskWatcherTest extends TestCase
{
    public function testATickIsATraceOfItsOwnType(): void
    {
        $processor = $this->createMock(Processor::class);

        $processor->expects($this->once())
            ->method('startAndGetTraceId')
            ->with(
                'task',
                ['check-watchers'],
                ['task' => 'check-watchers'],
                $this->isInstanceOf(Carbon::class),
                null
            )
            ->willReturn('trace-1');

        $processor->expects($this->once())
            ->method('stop')
            ->with(
                'trace-1',
                TraceStatusEnum::Success->value,
                null,
                ['task' => 'check-watchers', 'result' => 'worked'],
                $this->isType('float'),
                $this->isInstanceOf(Carbon::class)
            );

        $watcher = $this->watcher($processor);

        $watcher->handleTickStarted(new TaskTickStarted(name: 'check-watchers'));
        $watcher->handleTickFinished(
            new TaskTickFinished(
                name: 'check-watchers',
                result: TickResultEnum::Worked,
                exception: null
            )
        );
    }

    public function testATickThatThrewIsAFailedTrace(): void
    {
        $processor = $this->createMock(Processor::class);
        $processor->method('startAndGetTraceId')->willReturn('trace-1');

        $stopped = null;

        $processor->expects($this->once())
            ->method('stop')
            ->willReturnCallback(
                function (string $traceId, string $status, ?array $tags, ?array $data) use (&$stopped): void {
                    $stopped = ['status' => $status, 'data' => $data];
                }
            );

        $watcher = $this->watcher($processor);

        $watcher->handleTickStarted(new TaskTickStarted(name: 'cron'));
        $watcher->handleTickFinished(
            new TaskTickFinished(
                name: 'cron',
                result: TickResultEnum::Failed,
                exception: new RuntimeException('the schedule blew up')
            )
        );

        self::assertSame(TraceStatusEnum::Failed->value, $stopped['status']);
        self::assertSame('failed', $stopped['data']['result']);
        self::assertSame(RuntimeException::class, $stopped['data']['exception']['class']);
        self::assertSame('the schedule blew up', $stopped['data']['exception']['message']);
    }

    public function testAnExceptedTaskIsNotTraced(): void
    {
        $processor = $this->createMock(Processor::class);

        $processor->expects($this->never())->method('startAndGetTraceId');
        $processor->expects($this->never())->method('stop');

        $watcher = $this->watcher($processor, ['excepted' => ['build-trace-dynamic-indexes']]);

        $watcher->handleTickStarted(new TaskTickStarted(name: 'build-trace-dynamic-indexes'));
        $watcher->handleTickFinished(
            new TaskTickFinished(
                name: 'build-trace-dynamic-indexes',
                result: TickResultEnum::Idle,
                exception: null
            )
        );
    }

    public function testAFinishWithoutAStartClosesNothing(): void
    {
        $processor = $this->createMock(Processor::class);

        $processor->expects($this->never())->method('stop');

        $this->watcher($processor)->handleTickFinished(
            new TaskTickFinished(
                name: 'cron',
                result: TickResultEnum::Idle,
                exception: null
            )
        );
    }

    public function testATickClosesItsOwnTraceWhileAnotherTaskIsRunning(): void
    {
        $processor = $this->createMock(Processor::class);

        $processor->method('startAndGetTraceId')
            ->willReturnOnConsecutiveCalls('trace-cron', 'trace-watchers');

        $stoppedTraceIds = [];

        $processor->method('stop')
            ->willReturnCallback(
                function (string $traceId) use (&$stoppedTraceIds): void {
                    $stoppedTraceIds[] = $traceId;
                }
            );

        $watcher = $this->watcher($processor);

        $watcher->handleTickStarted(new TaskTickStarted(name: 'cron'));
        $watcher->handleTickStarted(new TaskTickStarted(name: 'check-watchers'));

        $watcher->handleTickFinished(
            new TaskTickFinished(name: 'check-watchers', result: TickResultEnum::Worked, exception: null)
        );
        $watcher->handleTickFinished(
            new TaskTickFinished(name: 'cron', result: TickResultEnum::Idle, exception: null)
        );

        self::assertSame(['trace-watchers', 'trace-cron'], $stoppedTraceIds);
    }

    /**
     * @param array<string, mixed>|null $config
     */
    private function watcher(Processor $processor, ?array $config = null): TaskWatcher
    {
        $watcher = new TaskWatcher(
            processor: $processor,
            context: new ArrayTraceContext()
        );

        $watcher->register($config);

        return $watcher;
    }
}
