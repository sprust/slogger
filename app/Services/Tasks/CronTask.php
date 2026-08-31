<?php

declare(strict_types=1);

namespace App\Services\Tasks;

use Illuminate\Support\Facades\Artisan;
use SConcur\Laravel\Tasks\TaskInterface;
use Illuminate\Support\Carbon;
use SConcur\Laravel\Tasks\TaskPoolLogger;
use SConcur\Laravel\Tasks\TickResultEnum;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Runs the application's schedule, once per minute.
 *
 * The pool ticks this more often than that on purpose: the minute is watched rather than
 * slept through, so a tick that lands late — a busy pool, a long neighbouring task —
 * still catches the minute it belongs to instead of skipping it.
 *
 * The last minute run is the only state here, and it is why the pool keeps a task
 * instance between ticks rather than building one for each.
 */
class CronTask implements TaskInterface
{
    public const string NAME = 'cron';

    private int $previousMinute;

    public function __construct(private readonly TaskPoolLogger $logger)
    {
        // The minute already in progress counts as run, which is what the cron command
        // this replaces did before entering its loop. Starting from "no minute has run"
        // would fire schedule:run again for a minute the previous process already
        // served, and every restart — deploy, memory limit, sconcur:tasks:restart —
        // would dispatch that minute's due jobs a second time.
        $this->previousMinute = $this->currentMinute();
    }

    /**
     * Carbon rather than date(), so the clock can be frozen: a test that has to catch the
     * constructor and the first tick inside one minute cannot race the real one.
     */
    private function currentMinute(): int
    {
        return Carbon::now()->minute;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function tick(): TickResultEnum
    {
        $minute = $this->currentMinute();

        if ($minute === $this->previousMinute) {
            return TickResultEnum::Idle;
        }

        $this->previousMinute = $minute;

        // The schedule's own output is worth keeping — it is where a failing scheduled
        // command shows up — but it goes through the pool's logger rather than straight
        // to stdout, so one log stays one format.
        //
        // Expect silence on the minutes nothing is due, and do not read it as a stalled
        // cron. ScheduleRunCommand::$eventsRan is a property it never resets, and the
        // console application keeps one instance of the command, so once an event has
        // run in this process its "No scheduled commands are ready to run." is never
        // printed again. What a due minute does is still logged, which is the half worth
        // having.
        $output = new BufferedOutput();

        Artisan::call('schedule:run', outputBuffer: $output);

        $this->report($output->fetch());

        return TickResultEnum::Worked;
    }

    private function report(string $output): void
    {
        foreach (explode(PHP_EOL, trim($output)) as $line) {
            $line = trim($line);

            if ($line !== '') {
                $this->logger->log(self::NAME, $line);
            }
        }
    }
}
