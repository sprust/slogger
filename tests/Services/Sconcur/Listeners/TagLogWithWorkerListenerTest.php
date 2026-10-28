<?php

declare(strict_types=1);

namespace Tests\Services\Sconcur\Listeners;

use Illuminate\Support\Facades\Log;
use SConcur\Laravel\Servers\Events\WorkerForked;
use SConcur\Worker\WorkerContext;
use Tests\TestCase;

class TagLogWithWorkerListenerTest extends TestCase
{
    public function testAForkedWorkerTagsTheLogWithItsGroupAndSlot(): void
    {
        event(
            new WorkerForked(
                new WorkerContext(group: 'http', slot: 1, masterPid: 4242)
            )
        );

        $this->assertSame(['worker' => 'http#1'], Log::sharedContext());
    }
}
