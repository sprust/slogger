<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services;

use LogicException;
use RuntimeException;
use App\Modules\Trace\Infrastructure\Jobs\BuildTraceTreeCacheJob;
use App\Modules\Trace\Infrastructure\Jobs\DeleteTraceTreeCacheJob;
use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Dashboard\Infrastructure\Jobs\RefreshDatabaseStatCacheJob;
use App\Modules\Notification\Infrastructure\Jobs\SendNotificationJob;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyJobPayloadTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function cases(): iterable
    {
        $jobClasses = [
            BuildTraceTreeCacheJob::class,
            DeleteTraceTreeCacheJob::class,
            ClearTracesJob::class,
            RefreshDatabaseStatCacheJob::class,
            SendNotificationJob::class,
        ];

        foreach ($jobClasses as $class) {
            yield $class => [$class];
        }
    }

    #[DataProvider('cases')]
    public function test(
        string $class
    ): void {
        config(
            key: [
                'module-trace.queue.connection' => 'sync',
                'module-trace.queue.name'       => 'traces',
                'cleaner.queue.connection'      => 'sync',
                'cleaner.queue.name'            => 'cleaner',
            ],
        );

        // Serialized by the job classes from the planning commit 71ee8ec9, before implementation.
        $payloads = json_decode(
            (file_get_contents(__DIR__ . '/Fixtures/legacy-jobs.json') ?: throw new RuntimeException('Missing JSON content')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $restored = unserialize(
            $payloads[$class],
            [
                'allowed_classes' => [$class],
            ],
        );
        $current = match (true) {
            is_a($class, BuildTraceTreeCacheJob::class, true) => new BuildTraceTreeCacheJob(
                rootTraceId: 'root',
                version: 'version',
                depth: 2,
                afterId: 'cursor',
            ),
            is_a($class, DeleteTraceTreeCacheJob::class, true) => new DeleteTraceTreeCacheJob(
                rootTraceId: 'root',
                buildVersion: 'version',
            ),
            is_a($class, ClearTracesJob::class, true)              => new ClearTracesJob(),
            is_a($class, RefreshDatabaseStatCacheJob::class, true) => new RefreshDatabaseStatCacheJob(),
            is_a($class, SendNotificationJob::class, true)         => new SendNotificationJob('incident-notification'),
            default                                                => throw new LogicException("Unsupported job: $class"),
        };

        $this->assertSame(
            $class,
            $restored::class
        );

        $this->assertEquals(
            $current,
            $restored
        );

        $this->assertSame(
            $payloads[$class],
            serialize($current)
        );
    }
}
