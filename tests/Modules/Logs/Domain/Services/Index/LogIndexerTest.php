<?php

namespace Tests\Modules\Logs\Domain\Services\Index;

use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Enums\HttpStatusClassEnum;
use App\Modules\Logs\Enums\LaravelLogLevelEnum;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Illuminate\Support\Facades\Cache;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogIndexerTest extends TestCase
{
    use LogsTempDirTrait;

    private const string ERROR_ENTRY = "[2026-09-25 05:35:29] local.ERROR: connect refused {\"exception\":\"[object] (E(code: 0): x at /app/a.php:1)\n[stacktrace]\n#0 /app/a.php(1): f()\n#1 {main}\n\"} \n";
    private const string INFO_ENTRY  = "[2026-09-25 05:35:30] local.INFO: started {\"worker\":1} \n";
    private const string DEBUG_ENTRY = "[2026-09-25 05:35:31] local.DEBUG: tick \n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        config()->set('cache.mutex_store', 'array');
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testAFileIsIndexedEntryByEntry(): void
    {
        $file = $this->writeLogFile('laravel.log', self::ERROR_ENTRY . self::INFO_ENTRY . self::DEBUG_ENTRY);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame($file->sizeBytes, $meta->indexedBytes);
        $this->assertTrue($meta->lastEntryOpen);
        $this->assertSame(3, $meta->entriesCount);
        $this->assertSame(
            [
                LaravelLogLevelEnum::Debug->value => 1,
                LaravelLogLevelEnum::Info->value  => 1,
                LaravelLogLevelEnum::Error->value => 1,
            ],
            $meta->levelCounts
        );
        $this->assertSame([self::ERROR_ENTRY, self::INFO_ENTRY, self::DEBUG_ENTRY], $this->readEntries($file));
        $this->assertSame(
            [gmmktime(5, 35, 29, 9, 25, 2026), gmmktime(5, 35, 30, 9, 25, 2026), gmmktime(5, 35, 31, 9, 25, 2026)],
            array_map(static fn(LogIndexRecordObject $record): int => $record->loggedAt, $this->readRecords($file))
        );
        $this->assertLevelFilesMatch($file);
    }

    public function testAnEntryStillBeingWrittenIsCompletedByTheNextRun(): void
    {
        $half = substr(self::ERROR_ENTRY, 0, 70);

        $file = $this->writeLogFile('laravel.log', $half);

        $this->indexer()->ensureFresh($file);

        $this->assertSame([$half], $this->readEntries($file));

        $file = $this->writeLogFile('laravel.log', substr(self::ERROR_ENTRY, 70) . self::INFO_ENTRY, append: true);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(2, $meta->entriesCount);
        $this->assertSame([self::ERROR_ENTRY, self::INFO_ENTRY], $this->readEntries($file));
        $this->assertSameAsFromScratch($file);
    }

    public function testAppendedEntriesKeepTheEarlierRecords(): void
    {
        $file = $this->writeLogFile('laravel.log', self::ERROR_ENTRY . self::INFO_ENTRY);

        $this->indexer()->ensureFresh($file);

        $before = $this->readRecords($file);

        $file = $this->writeLogFile('laravel.log', self::DEBUG_ENTRY . self::ERROR_ENTRY, append: true);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(4, $meta->entriesCount);
        $this->assertEquals($before[0], $this->readRecords($file)[0]);
        $this->assertSameAsFromScratch($file);
    }

    public function testAShrunkFileIsIndexedAgain(): void
    {
        $file = $this->writeLogFile('laravel.log', self::ERROR_ENTRY . self::INFO_ENTRY . self::DEBUG_ENTRY);

        $this->indexer()->ensureFresh($file);

        $file = $this->writeLogFile('laravel.log', self::DEBUG_ENTRY);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(1, $meta->entriesCount);
        $this->assertSame([LaravelLogLevelEnum::Debug->value => 1], $meta->levelCounts);
        $this->assertSame([self::DEBUG_ENTRY], $this->readEntries($file));
        $this->assertLevelFilesMatch($file);
    }

    public function testAFileWithAnotherHeadIsIndexedAgain(): void
    {
        $file = $this->writeLogFile('laravel.log', self::INFO_ENTRY);

        $this->indexer()->ensureFresh($file);

        $replaced = str_replace('started', 'stopped', self::INFO_ENTRY);

        $file = $this->writeLogFile('laravel.log', $replaced . self::ERROR_ENTRY);

        $this->indexer()->ensureFresh($file);

        $this->assertSame([$replaced, self::ERROR_ENTRY], $this->readEntries($file));
        $this->assertSameAsFromScratch($file);
    }

    public function testSmallWindowsGiveTheSameIndex(): void
    {
        config()->set('module-logs.index.window_bytes', 16);

        $contents = str_repeat(self::ERROR_ENTRY . self::INFO_ENTRY . self::DEBUG_ENTRY, 20);

        $file = $this->writeLogFile('laravel.log', $contents);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(60, $meta->entriesCount);
        $this->assertSame(strlen($contents), $meta->indexedBytes);
        $this->assertSame($contents, implode('', $this->readEntries($file)));

        config()->set('module-logs.index.window_bytes', 4 * 1024 * 1024);

        $this->assertSameAsFromScratch($file);
    }

    public function testTextBeforeTheFirstHeaderIsAnEntryWithoutLevel(): void
    {
        $file = $this->writeLogFile('laravel.log', "left over from a rotated file\n" . self::INFO_ENTRY);

        $this->indexer()->ensureFresh($file);

        $records = $this->readRecords($file);

        $this->assertSame([0, LaravelLogLevelEnum::Info->value], [$records[0]->level, $records[1]->level]);
        $this->assertSame($records[1]->loggedAt, $records[0]->loggedAt);
    }

    public function testRecordsLeftByAnInterruptedRunAreDropped(): void
    {
        $file = $this->writeLogFile('laravel.log', self::ERROR_ENTRY . self::INFO_ENTRY);

        $this->indexer()->ensureFresh($file);

        $this->repository()->appendRecords($file->id, [
            new LogIndexRecordObject(entryNo: 99, offset: 1, length: 1, loggedAt: 1, level: LaravelLogLevelEnum::Emergency->value),
            new LogIndexRecordObject(entryNo: 100, offset: 1, length: 1, loggedAt: 1, level: LaravelLogLevelEnum::Info->value),
        ]);

        $file = $this->writeLogFile('laravel.log', self::DEBUG_ENTRY, append: true);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(3, $meta->entriesCount);
        $this->assertSame(0, $this->repository()->countRecords($file->id, LaravelLogLevelEnum::Emergency->value));
        $this->assertSameAsFromScratch($file);
    }

    public function testAFreshIndexIsReadWithoutTakingTheMutex(): void
    {
        $file = $this->writeLogFile('laravel.log', self::INFO_ENTRY);

        $meta = $this->indexer()->ensureFresh($file);

        Cache::store('array')->lock("mutex:logs-index:$file->id", 10)->get();

        $this->assertEquals($meta, $this->indexer()->ensureFresh($file));
    }

    public function testAnEmptyFileHasAnEmptyIndex(): void
    {
        $file = $this->writeLogFile('laravel.log', '');

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(0, $meta->entriesCount);
        $this->assertSame(0, $meta->indexedBytes);

        $file = $this->writeLogFile('laravel.log', self::INFO_ENTRY, append: true);

        $this->assertSame(1, $this->indexer()->ensureFresh($file)->entriesCount);
    }

    public function testAMissingFileRaises(): void
    {
        $file = $this->writeLogFile('laravel.log', '');

        unlink($file->path);

        $this->expectException(LogFileNotFoundException::class);

        $this->indexer()->ensureFresh($file);
    }

    public function testAnAccessLogLineWithoutATimeTakesThePreviousOne(): void
    {
        $first = '1.1.1.1 - - [25/Sep/2026:10:00:00 +0000] "GET / HTTP/1.1" 200 1 "-" "-"' . "\n";
        $third = '1.1.1.1 - - [25/Sep/2026:10:00:05 +0000] "GET / HTTP/1.1" 503 1 "-" "-"' . "\n";

        $file = $this->writeLogFile('access.log', $first . "broken\n" . $third, LogTypeEnum::NginxAccess);

        $meta = $this->indexer()->ensureFresh($file);

        $records = $this->readRecords($file);

        $this->assertSame([$first, "broken\n", $third], $this->readEntries($file));
        $this->assertSame([gmmktime(10, 0, 0, 9, 25, 2026), gmmktime(10, 0, 0, 9, 25, 2026)], [$records[0]->loggedAt, $records[1]->loggedAt]);
        $this->assertSame(
            [0 => 1, HttpStatusClassEnum::Success->value => 1, HttpStatusClassEnum::ServerError->value => 1],
            $meta->levelCounts
        );
    }

    public function testATimeBudgetStopsBetweenWindowsAndTheNextRunFinishes(): void
    {
        config()->set('module-logs.index.window_bytes', 64);

        $file = $this->writeLogFile('laravel.log', str_repeat(self::INFO_ENTRY, 300));

        $partial = $this->indexer()->ensureFresh($file, timeBudgetMs: 1);

        $this->assertLessThanOrEqual($file->sizeBytes, $partial->indexedBytes);

        $meta = $this->indexer()->ensureFresh($file);

        $this->assertSame(300, $meta->entriesCount);
        $this->assertSame($file->sizeBytes, $meta->indexedBytes);
        $this->assertSameAsFromScratch($file);
    }

    private function indexer(): LogIndexer
    {
        return $this->app->make(LogIndexer::class);
    }

    private function repository(): LogIndexRepository
    {
        return $this->app->make(LogIndexRepository::class);
    }

    /**
     * @return list<LogIndexRecordObject>
     */
    private function readRecords(LogFileObject $file, ?int $level = null): array
    {
        return $this->repository()->readRecords(
            fileId: $file->id,
            level: $level,
            from: 0,
            count: $this->repository()->countRecords($file->id, $level)
        );
    }

    /**
     * @return list<string>
     */
    private function readEntries(LogFileObject $file): array
    {
        $contents = (string) file_get_contents($file->path);

        return array_map(
            static fn(LogIndexRecordObject $record): string => substr($contents, $record->offset, $record->length),
            $this->readRecords($file)
        );
    }

    private function assertLevelFilesMatch(LogFileObject $file): void
    {
        $records = $this->readRecords($file);

        foreach ($records as $index => $record) {
            $this->assertSame($index, $record->entryNo);
        }

        foreach ($this->repository()->findLevels($file->id) as $level) {
            $this->assertEquals(
                array_values(
                    array_filter($records, static fn(LogIndexRecordObject $record): bool => $record->level === $level)
                ),
                $this->readRecords($file, $level)
            );
        }
    }

    private function assertSameAsFromScratch(LogFileObject $file): void
    {
        $this->assertLevelFilesMatch($file);

        $copy = $this->writeLogFile('scratch-' . $file->name, (string) file_get_contents($file->path), $file->type);

        $expected = $this->indexer()->ensureFresh($copy);
        $actual   = $this->repository()->findMeta($file->id);

        $this->assertNotNull($actual);
        $this->assertSame($expected->entriesCount, $actual->entriesCount);
        $this->assertSame($expected->levelCounts, $actual->levelCounts);
        $this->assertSame($expected->indexedBytes, $actual->indexedBytes);
        $this->assertEquals($this->readRecords($copy), $this->readRecords($file));
    }
}
