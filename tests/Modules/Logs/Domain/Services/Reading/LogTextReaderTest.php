<?php

namespace Tests\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Domain\Services\Reading\LogTextReader;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

class LogTextReaderTest extends TestCase
{
    use LogsTempDirTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testNearAndFarRecordsAreReadByTheirOwnBytes(): void
    {
        $contents = 'aaaa' . 'bbbb' . str_repeat('.', 200 * 1024) . 'cccc' . str_repeat('.', 100) . 'dddd';

        $file = $this->writeLogFile('laravel.log', $contents);

        $far = 8 + 200 * 1024;

        $texts = $this->app->make(LogTextReader::class)->read(
            path: $file->path,
            records: [
                $this->record(entryNo: 3, offset: $far + 104, length: 4),
                $this->record(entryNo: 0, offset: 0, length: 4),
                $this->record(entryNo: 2, offset: $far, length: 4),
                $this->record(entryNo: 1, offset: 4, length: 4),
            ],
            maxEntryBytes: 1024
        );

        ksort($texts);

        $this->assertSame([0 => 'aaaa', 1 => 'bbbb', 2 => 'cccc', 3 => 'dddd'], $texts);
    }

    public function testATextIsCutToTheLimit(): void
    {
        $file = $this->writeLogFile('laravel.log', 'abcdefgh');

        $texts = $this->app->make(LogTextReader::class)->read(
            path: $file->path,
            records: [$this->record(entryNo: 0, offset: 0, length: 8)],
            maxEntryBytes: 3
        );

        $this->assertSame([0 => 'abc'], $texts);
    }

    public function testNoRecordsReadNothing(): void
    {
        $this->assertSame([], $this->app->make(LogTextReader::class)->read('/missing', [], 10));
    }

    private function record(int $entryNo, int $offset, int $length): LogIndexRecordObject
    {
        return new LogIndexRecordObject(entryNo: $entryNo, offset: $offset, length: $length, loggedAt: 0, level: 0);
    }
}
