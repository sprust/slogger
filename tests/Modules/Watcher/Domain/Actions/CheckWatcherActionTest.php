<?php

namespace Tests\Modules\Watcher\Domain\Actions;

use App\Modules\Watcher\Domain\Actions\Mutations\CheckWatcherAction;
use App\Modules\Watcher\Domain\Actions\Mutations\RegisterTriggerAction;
use App\Modules\Watcher\Domain\Actions\Mutations\TrimWatcherTimelineAction;
use App\Modules\Watcher\Domain\Services\Checkers\BufferOverflowChecker;
use App\Modules\Watcher\Domain\Services\Checkers\InvalidBufferGrownChecker;
use App\Modules\Watcher\Domain\Services\Checkers\NoNewTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\SlowTracesChecker;
use App\Modules\Watcher\Domain\Services\Checkers\TracesSpikeChecker;
use App\Modules\Watcher\Domain\Services\Types\BufferOverflowWatcherType;
use App\Modules\Watcher\Domain\Services\Types\InvalidBufferGrownWatcherType;
use App\Modules\Watcher\Domain\Services\Types\NoNewTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\SlowTracesWatcherType;
use App\Modules\Watcher\Domain\Services\Types\TracesSpikeWatcherType;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;
use App\Modules\Watcher\Entities\WatcherCheckContextObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\WatcherRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Modules\Watcher\WatcherFactoryTrait;

class CheckWatcherActionTest extends TestCase
{
    use WatcherFactoryTrait;

    /**
     * A pass covers every watcher, and one with settings nothing can read is no reason to
     * leave the rest unchecked — least of all in the subsystem whose job is noticing
     * trouble.
     */
    public function testAFailingCheckDoesNotEscape(): void
    {
        $this->expectNotToPerformAssertions();

        $this->action(new RuntimeException('the line would not load'))->handle(
            $this->watcher(WatcherTypeEnum::BufferOverflow, new BufferOverflowSettingsObject()),
            new WatcherCheckContextObject(Carbon::now(), 0)
        );
    }

    /**
     * The moment of a check is the lower bound of the next one's window. Moving it past a
     * check that did not happen would skip whatever fell in between, which for the invalid
     * buffer means documents nobody is ever told about.
     */
    public function testAFailedCheckDoesNotCountAsHavingLooked(): void
    {
        $watchers = $this->createMock(WatcherRepository::class);
        $watchers->expects($this->never())->method('updateCheckedAt');

        $this->action(new RuntimeException('the line would not load'), $watchers)->handle(
            $this->watcher(WatcherTypeEnum::BufferOverflow, new BufferOverflowSettingsObject()),
            new WatcherCheckContextObject(Carbon::now(), 0)
        );
    }

    public function testASuccessfulCheckIsRecordedAsHavingLooked(): void
    {
        $watchers = $this->createMock(WatcherRepository::class);
        $watchers->expects($this->once())->method('updateCheckedAt');

        $this->action(null, $watchers)->handle(
            $this->watcher(WatcherTypeEnum::BufferOverflow, new BufferOverflowSettingsObject()),
            new WatcherCheckContextObject(Carbon::now(), 0)
        );
    }

    private function action(?RuntimeException $failure, ?WatcherRepository $watchers = null): CheckWatcherAction
    {
        $checker = $this->createMock(BufferOverflowChecker::class);

        if ($failure) {
            $checker->method('check')->willThrowException($failure);
        } else {
            $checker->method('check')->willReturn(null);
        }

        $registry = new WatcherTypeRegistry(
            new BufferOverflowWatcherType($checker),
            new InvalidBufferGrownWatcherType($this->createMock(InvalidBufferGrownChecker::class)),
            new NoNewTracesWatcherType($this->createMock(NoNewTracesChecker::class)),
            new TracesSpikeWatcherType($this->createMock(TracesSpikeChecker::class)),
            new SlowTracesWatcherType($this->createMock(SlowTracesChecker::class))
        );

        return new CheckWatcherAction(
            $registry,
            $this->createMock(RegisterTriggerAction::class),
            $this->createMock(TrimWatcherTimelineAction::class),
            $watchers ?? $this->createMock(WatcherRepository::class),
            $this->createMock(LoggerInterface::class)
        );
    }
}
