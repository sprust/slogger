<?php

declare(strict_types=1);

namespace Tests\Services\Sconcur\Listeners;

use Illuminate\Support\Facades\Log;
use SConcur\Laravel\Servers\Events\WorkerForked;
use SConcur\Worker\WorkerContext;
use Tests\TestCase;

class TagLogWithWorkerListenerTest extends TestCase
{
    protected bool $fakeEvents = false;

    public function testAForkedWorkerTagsTheLogWithItsGroupAndSlot(): void
    {
        event(
            new WorkerForked(
                workerContext: new WorkerContext(
                    group: 'http',
                    slot: 1,
                    masterPid: 4242,
                ),
            )
        );

        $this->assertSame(
            expected: [
                'worker' => 'http#1',
            ],
            actual: Log::sharedContext()
        );
    }
}
