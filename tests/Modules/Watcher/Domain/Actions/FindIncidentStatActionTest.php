<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Queries\FindIncidentStatAction;
use App\Modules\Watcher\Repositories\WatcherIncidentRepository;
use PHPUnit\Framework\TestCase;

/**
 * The one number the header's badge stands on.
 */
class FindIncidentStatActionTest extends TestCase
{
    public function testItCountsTheOpenIncidents(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->expects($this->once())->method('countOpen')->willReturn(4);

        $this->assertSame(4, new FindIncidentStatAction($incidents)->handle()->openedCount);
    }

    /**
     * Nothing open is a number too. The badge is hidden on zero rather than left showing
     * whatever it was before, so this has to come back as a count and not as an absence.
     */
    public function testNothingOpenIsZero(): void
    {
        $incidents = $this->createMock(WatcherIncidentRepository::class);
        $incidents->method('countOpen')->willReturn(0);

        $this->assertSame(0, new FindIncidentStatAction($incidents)->handle()->openedCount);
    }
}
