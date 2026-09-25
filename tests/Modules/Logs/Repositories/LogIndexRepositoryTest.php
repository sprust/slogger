<?php

namespace Tests\Modules\Logs\Repositories;

use App\Modules\Logs\Entities\Index\LogIndexMetaObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogIndexRepositoryTest extends TestCase
{
    use LogsTempDirTrait;

    private LogIndexRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        $this->repository = $this->app->make(LogIndexRepository::class);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testRecordsSurviveTheRoundTripAndGoToTheirLevel(): void
    {
        $records = [
            new LogIndexRecordObject(entryNo: 0, offset: 0, length: 10, loggedAt: 1_790_000_000, level: 5),
            new LogIndexRecordObject(entryNo: 1, offset: 5_000_000_000, length: 4_000_000_000, loggedAt: 4_000_000_000, level: 0),
            new LogIndexRecordObject(entryNo: 4_294_967_295, offset: 7, length: 1, loggedAt: 1, level: 5),
        ];

        $this->repository->appendRecords('f', $records);

        $this->assertEquals($records, $this->repository->readRecords('f', null, 0, 10));
        $this->assertEquals([$records[0], $records[2]], $this->repository->readRecords('f', 5, 0, 10));
        $this->assertEquals([$records[1]], $this->repository->readRecords('f', 0, 0, 10));
        $this->assertEquals([$records[1]], $this->repository->readRecords('f', null, 1, 1));
        $this->assertSame(3, $this->repository->countRecords('f', null));
        $this->assertSame(2, $this->repository->countRecords('f', 5));
        $this->assertSame(0, $this->repository->countRecords('f', 3));
        $this->assertSame([0, 5], $this->repository->findLevels('f'));
    }

    public function testRecordsAreTruncatedByCount(): void
    {
        $this->repository->appendRecords('f', [
            new LogIndexRecordObject(entryNo: 0, offset: 0, length: 1, loggedAt: 1, level: 1),
            new LogIndexRecordObject(entryNo: 1, offset: 1, length: 1, loggedAt: 1, level: 1),
        ]);

        $this->repository->truncateRecords('f', null, 1);
        $this->repository->truncateRecords('f', 1, 0);
        $this->repository->truncateRecords('f', 9, 0);

        $this->assertSame(1, $this->repository->countRecords('f', null));
        $this->assertSame(0, $this->repository->countRecords('f', 1));
    }

    public function testMetaSurvivesTheRoundTrip(): void
    {
        $meta = new LogIndexMetaObject(
            version: 1,
            path: '/app/storage/logs/laravel.log',
            type: LogTypeEnum::NginxAccess,
            indexedBytes: 5_000_000_000,
            lastEntryOpen: true,
            headLength: 1024,
            headHash: md5('head'),
            entriesCount: 3,
            levelCounts: [0 => 1, 5 => 2]
        );

        $this->repository->saveMeta('f', $meta);

        $this->assertEquals($meta, $this->repository->findMeta('f'));
    }

    public function testAMissingIndexHasNoMetaAndNoRecords(): void
    {
        $this->assertNull($this->repository->findMeta('missing'));
        $this->assertSame(0, $this->repository->countRecords('missing', null));
        $this->assertSame([], $this->repository->findLevels('missing'));
    }

    public function testDeleteRemovesTheWholeIndex(): void
    {
        $this->repository->appendRecords('f', [
            new LogIndexRecordObject(entryNo: 0, offset: 0, length: 1, loggedAt: 1, level: 1),
        ]);

        $this->repository->delete('f');
        $this->repository->delete('f');

        $this->assertSame(0, $this->repository->countRecords('f', null));
        $this->assertDirectoryDoesNotExist($this->tempDir . '/index/f');
    }
}
