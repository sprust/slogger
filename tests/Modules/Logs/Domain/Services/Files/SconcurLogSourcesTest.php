<?php

namespace Tests\Modules\Logs\Domain\Services\Files;

use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Entities\File\LogFileObject;
use Tests\Modules\Logs\LogsTempDirTrait;
use Tests\TestCase;

/**
 * The access files and the journal share a folder and a name prefix, so only the patterns
 * of the configured sources keep them apart.
 */
class SconcurLogSourcesTest extends TestCase
{
    use LogsTempDirTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->makeTempDir();

        $sources = [];

        foreach ((array) config('module-logs.sources') as $source) {
            if ($source['name'] === 'Sconcur') {
                $sources[] = ['folder' => $this->tempDir . '/logs'] + $source;
            }
        }

        config()->set('module-logs.sources', $sources);
    }

    protected function tearDown(): void
    {
        $this->removeTempDir();

        parent::tearDown();
    }

    public function testTheAccessFilesAndTheJournalGoToTheirOwnTypes(): void
    {
        $this->writeLogFile('sconcur-http-server-2026-10-28.log', '');
        $this->writeLogFile('sconcur-http-server-access-2026-10-28.log', '');

        $types = [];

        foreach (app(LogFileFinder::class)->findAll() as $file) {
            $types[$file->name] = $file->type->value;
        }

        ksort($types);

        $this->assertSame(
            [
                'sconcur-http-server-2026-10-28.log'        => 'sconcur_journal',
                'sconcur-http-server-access-2026-10-28.log' => 'sconcur_access',
            ],
            $types
        );
    }

    public function testTheFilesAreKeptAsLongAsTheMasterKeepsThem(): void
    {
        $this->writeLogFile('sconcur-http-server-access-2026-10-28.log', '');

        $keepDays = array_map(static fn(LogFileObject $file): ?int => $file->keepDays, app(LogFileFinder::class)->findAll());

        $this->assertSame([(int) config('sconcur.master.rotateDays')], $keepDays);
    }
}
