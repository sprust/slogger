<?php

namespace Tests\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Domain\Services\Reading\LogFileStream;
use App\Modules\Logs\Domain\Services\Reading\LogFileStreamFactory;
use App\Modules\Logs\Entities\Entry\LogEntryObject;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Enums\LaravelLogLevelEnum;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Parameters\LogEntriesFilterParameters;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogFileStreamTest extends TestCase
{
    use LogsTempDirTrait;

    private const int ENTRIES  = 1300;
    private const array LEVELS = ['DEBUG', 'INFO', 'ERROR', 'WARNING'];

    private LogFileObject $file;

    /**
     * @var list<array{entryNo: int, time: int, level: int, text: string}>
     */
    private array $expected = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        config()->set('cache.mutex_store', 'array');

        $base = gmmktime(0, 0, 0, 9, 25, 2026);

        $contents = '';

        for ($entryNo = 0; $entryNo < self::ENTRIES; ++$entryNo) {
            $time  = $base + $entryNo;
            $level = self::LEVELS[$entryNo % 4];
            $word  = $entryNo % 100 === 0 ? 'Привет' : 'hello';

            $text = sprintf("[%s] local.%s: %s %d {\"n\":%d} \n", gmdate('Y-m-d H:i:s', $time), $level, $word, $entryNo, $entryNo);

            if ($entryNo % 250 === 0) {
                $text .= "[stacktrace]\n#0 {main}\n";
            }

            $contents .= $text;

            $this->expected[] = [
                'entryNo' => $entryNo,
                'time'    => $time,
                'level'   => constant(LaravelLogLevelEnum::class . '::' . ucfirst(strtolower($level)))->value,
                'text'    => $text,
            ];
        }

        $this->file = $this->writeLogFile('laravel.log', $contents);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testAllEntriesComeNewestFirst(): void
    {
        $stream = $this->makeStream(new LogEntriesFilterParameters());

        $this->assertSame(self::ENTRIES, $stream->getTotal());
        $this->assertSame(self::ENTRIES - 1, $stream->getPosition());

        $entries = $this->drain($stream, 7);

        $this->assertSame(array_reverse(array_column($this->expected, 'entryNo')), $this->entryNos($entries));
        $this->assertSame($this->expected[5]['text'], $this->findEntry($entries, 5)->text);
        $this->assertTrue($stream->isExhausted());
        $this->assertNull($stream->getPosition());
        $this->assertSame(self::ENTRIES, $stream->getScannedRecords());
    }

    public function testNewerEntriesComeOldestFirstFromThePosition(): void
    {
        $stream = $this->makeStream(new LogEntriesFilterParameters(), LogCursorDirectionEnum::Newer, position: 1290);

        $this->assertSame(range(1290, 1299), $this->entryNos($this->drain($stream, 3)));
    }

    public function testOlderEntriesStartAtThePositionInclusive(): void
    {
        $stream = $this->makeStream(new LogEntriesFilterParameters(), position: 4);

        $this->assertSame([4, 3, 2, 1, 0], $this->entryNos($this->drain($stream, 2)));
    }

    public function testOneLevelComesFromItsOwnIndex(): void
    {
        $error = LaravelLogLevelEnum::Error->value;

        $stream = $this->makeStream(new LogEntriesFilterParameters(levels: [$error]));

        $this->assertSame(self::ENTRIES / 4, $stream->getTotal());
        $this->assertSame(
            $this->expectedEntryNos(static fn(array $entry): bool => $entry['level'] === $error),
            $this->entryNos($this->drain($stream, 11))
        );
    }

    public function testSeveralLevelsAreMergedInOrder(): void
    {
        $levels = [LaravelLogLevelEnum::Warning->value, LaravelLogLevelEnum::Debug->value];

        $stream = $this->makeStream(new LogEntriesFilterParameters(levels: $levels), position: 1000);

        $this->assertSame(self::ENTRIES / 2, $stream->getTotal());
        $this->assertSame(
            $this->expectedEntryNos(static fn(array $entry): bool => in_array($entry['level'], $levels, true) && $entry['entryNo'] <= 1000),
            $this->entryNos($this->drain($stream, 13))
        );
    }

    public function testSeveralLevelsGoForwardToo(): void
    {
        $levels = [LaravelLogLevelEnum::Warning->value, LaravelLogLevelEnum::Info->value];

        $stream = $this->makeStream(new LogEntriesFilterParameters(levels: $levels), LogCursorDirectionEnum::Newer, position: 600);

        $this->assertSame(
            array_reverse(
                $this->expectedEntryNos(static fn(array $entry): bool => in_array($entry['level'], $levels, true) && $entry['entryNo'] >= 600)
            ),
            $this->entryNos($this->drain($stream, 500))
        );
    }

    public function testALevelNotInTheFileGivesNothing(): void
    {
        $stream = $this->makeStream(new LogEntriesFilterParameters(levels: [LaravelLogLevelEnum::Emergency->value]));

        $this->assertSame(0, $stream->getTotal());
        $this->assertTrue($stream->isExhausted());
        $this->assertSame([], $stream->next());
    }

    public function testATimeRangeBoundsTheEntries(): void
    {
        $from = $this->expected[100]['time'];
        $to   = $this->expected[700]['time'];

        $stream = $this->makeStream(
            new LogEntriesFilterParameters(levels: [LaravelLogLevelEnum::Error->value, LaravelLogLevelEnum::Info->value], fromTime: $from, toTime: $to)
        );

        $expected = $this->expectedEntryNos(
            static fn(array $entry): bool => $entry['entryNo'] >= 100 && $entry['entryNo'] <= 700
                && in_array($entry['level'], [LaravelLogLevelEnum::Error->value, LaravelLogLevelEnum::Info->value], true)
        );

        $this->assertSame(count($expected), $stream->getTotal());
        $this->assertSame($expected, $this->entryNos($this->drain($stream, 500)));
    }

    public function testSearchIsCaseInsensitiveForAnyAlphabet(): void
    {
        $stream = $this->makeStream(new LogEntriesFilterParameters(searchQuery: 'пРИВЕТ'));

        $entries = $this->drain($stream, 500);

        $this->assertSame([1200, 1100, 1000, 900, 800, 700, 600, 500, 400, 300, 200, 100, 0], $this->entryNos($entries));
        $this->assertSame(self::ENTRIES, $stream->getTotal());
        $this->assertSame(self::ENTRIES, $stream->getScannedRecords());
        $this->assertGreaterThan(0, $stream->getScannedBytes());

        $stream = $this->makeStream(new LogEntriesFilterParameters(searchQuery: 'STACKTRACE'));

        $this->assertSame([1250, 1000, 750, 500, 250, 0], $this->entryNos($this->drain($stream, 500)));
    }

    public function testSearchStopsAfterTheExaminedRecords(): void
    {
        $stream = $this->makeStream(new LogEntriesFilterParameters(searchQuery: 'hello 1298'));

        $this->assertSame([1298], $this->entryNos($stream->next(10)));
        $this->assertSame(10, $stream->getScannedRecords());
        $this->assertSame(1289, $stream->getPosition());
        $this->assertSame($this->expected[1290]['time'], $stream->getLastTime());
        $this->assertSame($this->expected[1289]['time'], $stream->getNextTime());
    }

    public function testALongEntryIsCut(): void
    {
        config()->set('module-logs.reading.max_entry_bytes', 20);

        $entry = $this->makeStream(new LogEntriesFilterParameters())->next(1)[0];

        $this->assertSame(substr($this->expected[1299]['text'], 0, 20), $entry->text);
        $this->assertTrue($entry->truncated);
    }

    private function makeStream(
        LogEntriesFilterParameters $filter,
        LogCursorDirectionEnum $direction = LogCursorDirectionEnum::Older,
        ?int $position = null
    ): LogFileStream {
        $meta = $this->app->make(LogIndexer::class)->ensureFresh($this->file);

        return $this->app->make(LogFileStreamFactory::class)->make(
            file: $this->file,
            meta: $meta,
            filter: $filter,
            direction: $direction,
            position: $position
        );
    }

    /**
     * @return list<LogEntryObject>
     */
    private function drain(LogFileStream $stream, int $maxRecords): array
    {
        $entries = [];

        while (!$stream->isExhausted()) {
            array_push($entries, ...$stream->next($maxRecords));
        }

        return $entries;
    }

    /**
     * @param list<LogEntryObject> $entries
     *
     * @return list<int>
     */
    private function entryNos(array $entries): array
    {
        return array_map(static fn(LogEntryObject $entry): int => $entry->entryNo, $entries);
    }

    /**
     * @param list<LogEntryObject> $entries
     */
    private function findEntry(array $entries, int $entryNo): LogEntryObject
    {
        foreach ($entries as $entry) {
            if ($entry->entryNo === $entryNo) {
                return $entry;
            }
        }

        $this->fail("Entry $entryNo not found");
    }

    /**
     * @param callable(array{entryNo: int, time: int, level: int, text: string}): bool $filter
     *
     * @return list<int>
     */
    private function expectedEntryNos(callable $filter): array
    {
        return array_reverse(array_column(array_values(array_filter($this->expected, $filter)), 'entryNo'));
    }
}
