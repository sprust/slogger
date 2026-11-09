<?php

declare (strict_types=1);

namespace Tests\Modules\Common\Infrastructure\Services;

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
        foreach (
            [
                BuildTraceTreeCacheJob::class,
                DeleteTraceTreeCacheJob::class,
                ClearTracesJob::class,
                RefreshDatabaseStatCacheJob::class,
                SendNotificationJob::class,
            ] as $class
        ) {
            yield $class => [
                $class,
            ];
        }
    }

    #[DataProvider('cases')]
    public function test(string $class): void
    {
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
            file_get_contents(__DIR__ . '/Fixtures/legacy-jobs.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $restored = unserialize(
            $payloads[$class],
            [
                'allowed_classes' => [
                    $class,
                ],
            ],
        );
        $current = match ($class) {
            BuildTraceTreeCacheJob::class => new BuildTraceTreeCacheJob(
                rootTraceId: 'root',
                version: 'version',
                depth: 2,
                afterId: 'cursor',
            ),
            DeleteTraceTreeCacheJob::class => new DeleteTraceTreeCacheJob(
                rootTraceId: 'root',
                buildVersion: 'version',
            ),
            ClearTracesJob::class              => new ClearTracesJob(),
            RefreshDatabaseStatCacheJob::class => new RefreshDatabaseStatCacheJob(),
            SendNotificationJob::class         => new SendNotificationJob('incident-notification'),
        };

        $this->assertSame($class, $restored::class);

        $this->assertEquals($current, $restored);

        $this->assertSame($payloads[$class], serialize($current));
    }
}
