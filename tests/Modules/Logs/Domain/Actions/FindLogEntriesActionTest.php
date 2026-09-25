<?php

namespace Tests\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Entities\Entry\LogLevelKeyCountObject;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;
use App\Modules\Logs\Domain\Actions\FindLogEntriesAction;
use App\Modules\Logs\Domain\Exceptions\LogCursorInvalidException;
use App\Modules\Logs\Entities\Entry\LogEntriesPageObject;
use App\Modules\Logs\Entities\Entry\LogEntryViewObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use Illuminate\Support\Facades\Cache;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class FindLogEntriesActionTest extends TestCase
{
    use LogsTempDirTrait;

    /**
     * @var array<string, string>
     */
    private array $paths = [];

    /**
     * @var list<array{file: string, time: int, text: string}>
     */
    private array $expected = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        mkdir($this->tempDir . '/logs/slogger');
        mkdir($this->tempDir . '/logs/nginx');

        config()->set('cache.mutex_store', 'array');
        config()->set('module-logs.sources', [
            ['name' => 'Laravel', 'folder' => $this->tempDir . '/logs', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Slogger', 'folder' => $this->tempDir . '/logs/slogger', 'pattern' => '*.log', 'type' => LogTypeEnum::Laravel],
            ['name' => 'Nginx', 'folder' => $this->tempDir . '/logs/nginx', 'pattern' => 'access*.log', 'type' => LogTypeEnum::NginxAccess],
        ]);

        $day1 = gmmktime(0, 0, 0, 9, 24, 2026);
        $day2 = gmmktime(0, 0, 0, 9, 25, 2026);

        $this->writeLaravel('laravel-day1', 'logs/laravel-2026-09-24.log', $day1, 40, 60);
        $this->writeLaravel('laravel-day2', 'logs/laravel-2026-09-25.log', $day2, 40, 60);
        $this->writeLaravel('slogger-day2', 'logs/slogger/slogger-2026-09-25.log', $day2 + 30, 25, 45);
        $this->writeAccess('access-day2', 'logs/nginx/access-2026-09-25.log', $day2 + 10, 30, 70);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testFilesAreMergedNewestFirstByTime(): void
    {
        $page = $this->find(perPage: 1000);

        $this->assertSame($this->expectedTexts(), $this->texts($page));
        $this->assertSame(count($this->expected), $page->total);
        $this->assertNull($page->olderCursor);
        $this->assertNotNull($page->newerCursor);
        $this->assertFalse($page->indexing);
    }

    public function testPagesGoThroughEveryEntryOnce(): void
    {
        $this->assertSame($this->expectedTexts(), $this->collectPages(perPage: 7));
    }

    public function testASearchAcrossFilesFindsEveryMatchOnceEvenWhenTheBudgetCutsIt(): void
    {
        config()->set('module-logs.search.bytes_budget', 1);
        config()->set('module-logs.search.block_records', 4);

        $expected = $this->expectedTexts(static fn(array $entry): bool => str_contains($entry['text'], 'needle'));

        $this->assertNotEmpty($expected);
        $this->assertSame($expected, $this->collectPages(perPage: 3, searchQuery: 'NEEDLE'));
    }

    public function testAnEmptyPageFromTheBudgetStillMovesTheCursor(): void
    {
        config()->set('module-logs.search.bytes_budget', 1);
        config()->set('module-logs.search.block_records', 4);

        $page = $this->find(perPage: 50, searchQuery: 'no such text anywhere');

        $this->assertSame([], $page->items);
        $this->assertNotNull($page->olderCursor);
        $this->assertGreaterThan(0, $page->scanned);

        $pages   = 1;
        $scanned = $page->scanned;

        while ($page->olderCursor !== null) {
            $page = $this->find(perPage: 50, searchQuery: 'no such text anywhere', cursor: $page->olderCursor);

            $this->assertSame([], $page->items);

            $scanned += $page->scanned;

            $this->assertLessThan(1000, ++$pages);
        }

        $this->assertGreaterThan(1, $pages);
        $this->assertSame(count($this->expected), $scanned);
    }

    public function testTheNewerCursorGoesBackToThePreviousPage(): void
    {
        $first  = $this->find(perPage: 10);
        $second = $this->find(perPage: 10, cursor: $first->olderCursor);

        $this->assertNotNull($second->newerCursor);

        $back = $this->find(perPage: 10, cursor: $second->newerCursor);

        $this->assertSame($this->texts($first), $this->texts($back));
    }

    public function testEntriesWrittenAfterTheFirstPageComeThroughTheNewerCursor(): void
    {
        $first = $this->find(perPage: 5);

        $line = sprintf("[%s] local.ERROR: late arrival \n", gmdate('Y-m-d H:i:s', gmmktime(23, 0, 0, 9, 25, 2026)));

        file_put_contents($this->paths['laravel-day2'], $line, FILE_APPEND);

        $newer = $this->find(perPage: 5, cursor: $first->newerCursor);

        $this->assertSame([$line], $this->texts($newer));
    }

    public function testLevelKeysApplyToFilesOfTheirType(): void
    {
        $page = $this->find(perPage: 1000, levels: ['laravel.ERROR', 'nginx_access.5xx', 'laravel.unknown', 'bogus.ERROR']);

        $expected = $this->expectedTexts(
            static fn(array $entry): bool => str_contains($entry['text'], 'local.ERROR') || str_contains($entry['text'], '" 503 ')
        );

        $this->assertSame($expected, $this->texts($page));
        $this->assertSame(count($expected), $page->total);
    }

    public function testAFileWithoutSelectedLevelsOfItsTypeGivesNothing(): void
    {
        $page = $this->find(perPage: 1000, levels: ['nginx_access.5xx']);

        $this->assertSame(15, $page->total);
        $this->assertSame(
            $this->expectedTexts(static fn(array $entry): bool => str_contains($entry['text'], '" 503 ')),
            $this->texts($page)
        );
    }

    public function testNoLevelKeysMeanNoLevelFilter(): void
    {
        $this->assertSame(count($this->expected), $this->find(perPage: 1, levels: [])->total);
    }

    public function testLevelCountsCoverTheWholeScope(): void
    {
        $page = $this->find(perPage: 1);

        $this->assertEquals(
            [
                new LogLevelKeyCountObject(key: 'laravel.ERROR', count: 20 + 20 + 13),
                new LogLevelKeyCountObject(key: 'laravel.INFO', count: 20 + 20 + 12),
                new LogLevelKeyCountObject(key: 'nginx_access.2xx', count: 15),
                new LogLevelKeyCountObject(key: 'nginx_access.5xx', count: 15),
            ],
            $page->levelCounts
        );
    }

    public function testATimeRangeBoundsEveryFile(): void
    {
        $from = gmmktime(0, 0, 20, 9, 25, 2026);
        $to   = gmmktime(0, 0, 50, 9, 25, 2026);

        $page = $this->find(perPage: 1000, fromTime: $from, toTime: $to);

        $this->assertSame(
            $this->expectedTexts(static fn(array $entry): bool => $entry['time'] >= $from && $entry['time'] <= $to),
            $this->texts($page)
        );
    }

    public function testAnEntryCarriesItsFileTypeLevelAndDetails(): void
    {
        $page = $this->find(perPage: 1000, levels: ['nginx_access.5xx']);

        $entry = $page->items[0];

        $this->assertSame(sha1($this->paths['access-day2']), $entry->fileId);
        $this->assertSame(LogTypeEnum::NginxAccess, $entry->type);
        $this->assertSame('nginx_access.5xx', $entry->levelKey);
        $this->assertContainsEquals(new LogEntryFieldObject(key: 'status', value: '503'), $entry->details->fields);
    }

    public function testAnUnknownFileIsReportedAndTheRestAreRead(): void
    {
        $page = $this->find(perPage: 1000, fileIds: [sha1($this->paths['slogger-day2']), sha1('/etc/passwd')]);

        $this->assertSame([sha1('/etc/passwd')], $page->missingFileIds);
        $this->assertSame(25, $page->total);
    }

    public function testARotatedFileStartsAgainAndIsReported(): void
    {
        $first = $this->find(perPage: 5);

        file_put_contents(
            $this->paths['laravel-day2'],
            sprintf("[%s] local.INFO: fresh file \n", gmdate('Y-m-d H:i:s', gmmktime(0, 0, 0, 9, 26, 2026)))
        );

        $next = $this->find(perPage: 5, cursor: $first->olderCursor);

        $this->assertSame([sha1($this->paths['laravel-day2'])], $next->restartedFileIds);
    }

    public function testACursorForAnotherScopeIsRefused(): void
    {
        $first = $this->find(perPage: 5);

        $this->expectException(LogCursorInvalidException::class);

        $this->find(perPage: 5, fileIds: [sha1($this->paths['slogger-day2'])], cursor: $first->olderCursor);
    }

    public function testAMalformedCursorIsRefused(): void
    {
        $this->expectException(LogCursorInvalidException::class);

        $this->find(perPage: 5, cursor: 'bm90IGEgY3Vyc29y');
    }

    public function testAFileBeingIndexedElsewhereAnswersWithIndexing(): void
    {
        config()->set('module-logs.search.index_budget_ms', 1000);

        Cache::store('array')->lock('mutex:logs-index:' . sha1($this->paths['laravel-day1']), 10)->get();

        $page = $this->find(perPage: 5);

        $this->assertTrue($page->indexing);
        $this->assertSame([], $page->items);
        $this->assertGreaterThan(0, $page->totalBytes);
    }

    /**
     * @param list<string>|null $fileIds
     * @param list<string>|null $levels
     */
    private function find(
        int $perPage,
        ?array $fileIds = null,
        ?array $levels = null,
        ?int $fromTime = null,
        ?int $toTime = null,
        ?string $searchQuery = null,
        ?string $cursor = null
    ): LogEntriesPageObject {
        return $this->app->make(FindLogEntriesAction::class)->handle(
            new FindLogEntriesParameters(
                fileIds: $fileIds ?? array_values(array_map(sha1(...), $this->paths)),
                levelKeys: $levels,
                fromTime: $fromTime,
                toTime: $toTime,
                searchQuery: $searchQuery,
                cursor: $cursor,
                direction: LogCursorDirectionEnum::Older,
                perPage: $perPage
            )
        );
    }

    /**
     * @return list<string>
     */
    private function collectPages(int $perPage, ?string $searchQuery = null): array
    {
        $texts  = [];
        $cursor = null;
        $pages  = 0;

        do {
            $page = $this->find(perPage: $perPage, searchQuery: $searchQuery, cursor: $cursor);

            $this->assertLessThanOrEqual($perPage, count($page->items));

            array_push($texts, ...$this->texts($page));

            $cursor = $page->olderCursor;

            $this->assertLessThan(1000, ++$pages);
        } while ($cursor !== null);

        return $texts;
    }

    /**
     * @return list<string>
     */
    private function texts(LogEntriesPageObject $page): array
    {
        return array_map(static fn(LogEntryViewObject $entry): string => $entry->text, $page->items);
    }

    /**
     * @param callable(array{file: string, time: int, text: string}): bool|null $filter
     *
     * @return list<string>
     */
    private function expectedTexts(?callable $filter = null): array
    {
        $order = array_flip(array_keys($this->paths));

        $entries = [];

        foreach ($this->expected as $sequence => $entry) {
            if ($filter === null || $filter($entry)) {
                $entries[] = [...$entry, 'sequence' => $sequence];
            }
        }

        usort(
            $entries,
            static fn(array $left, array $right): int => [$right['time'], $order[$left['file']], $right['sequence']]
                <=> [$left['time'], $order[$right['file']], $left['sequence']]
        );

        return array_column($entries, 'text');
    }

    private function writeLaravel(string $key, string $relativePath, int $start, int $count, int $step): void
    {
        $contents = '';

        for ($index = 0; $index < $count; ++$index) {
            $time  = $start + intdiv($index * $step, 60);
            $level = $index % 2 === 0 ? 'ERROR' : 'INFO';
            $word  = $index % 5 === 0 ? 'needle' : 'hay';

            $text = sprintf("[%s] local.%s: %s %s %d \n", gmdate('Y-m-d H:i:s', $time), $level, $key, $word, $index);

            $contents .= $text;

            $this->expected[] = ['file' => $key, 'time' => $time, 'text' => $text];
        }

        $this->writeFile($key, $relativePath, $contents);
    }

    private function writeAccess(string $key, string $relativePath, int $start, int $count, int $step): void
    {
        $contents = '';

        for ($index = 0; $index < $count; ++$index) {
            $time   = $start + intdiv($index * $step, 60);
            $status = $index % 2 === 0 ? 200 : 503;
            $path   = $index % 5 === 0 ? '/needle' : '/hay';

            $text = sprintf(
                "1.1.1.1 - - [%s +0000] \"GET %s/%d HTTP/1.1\" %d 1 \"-\" \"-\"\n",
                gmdate('d/M/Y:H:i:s', $time),
                $path,
                $index,
                $status
            );

            $contents .= $text;

            $this->expected[] = ['file' => $key, 'time' => $time, 'text' => $text];
        }

        $this->writeFile($key, $relativePath, $contents);
    }

    private function writeFile(string $key, string $relativePath, string $contents): void
    {
        $path = sprintf('%s/%s', $this->tempDir, $relativePath);

        file_put_contents($path, $contents);

        $this->paths[$key] = $path;
    }
}
