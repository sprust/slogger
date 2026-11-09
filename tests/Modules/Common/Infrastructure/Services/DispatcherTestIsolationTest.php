<?php

declare(strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use stdClass;
use Tests\Modules\BaseModuleTestCase;

class DispatcherTestIsolationTest extends BaseModuleTestCase
{
    public function test(): void
    {
        Event::listen(
            events: stdClass::class,
            listener: static function (): void {
                self::fail('A faked domain event reached its real listener.');
            },
        );
        Queue::fake();

        $dispatcher = $this->appMake(DispatcherInterface::class);
        $event      = new stdClass();

        $dispatcher->event($event);
        $dispatcher->dispatch($this->appMake(ClearTracesJob::class));

        Event::assertDispatched(
            event: stdClass::class,
            callback: static fn(stdClass $dispatched): bool => $dispatched === $event
        );
        Queue::assertPushed(
            job: ClearTracesJob::class,
            callback: 1
        );
    }
}
