<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * `tracesSpike` became `manyTraces`: a count against a limit instead of a rise over the
 * stretch before it.
 *
 * The old settings do not convert. "90% above the previous hour" is not a number of
 * traces, and there is no arithmetic that turns one into the other — it depends on how
 * much traffic the service happens to carry. So the window and the filter are kept, the
 * limit is written at its default, and the watcher is switched off: it is measuring
 * something else now, and somebody has to say what number is too many. A watcher left on
 * with a limit nobody chose would either say nothing for ever or say it every cooldown.
 */
return new class extends Migration {
    private const string OLD_TYPE = 'tracesSpike';
    private const string NEW_TYPE = 'manyTraces';

    /** The same default the type definition offers a new watcher. */
    private const int DEFAULT_THRESHOLD = 1000;

    private const int DEFAULT_WINDOW_MINUTES = 5;

    public function up(): void
    {
        foreach ($this->watchersOfType(self::OLD_TYPE) as $watcher) {
            $settings = $this->settingsOf($watcher);

            DB::table('watchers')
                ->where('id', $watcher->id)
                ->update([
                    'type'     => self::NEW_TYPE,
                    'enabled'  => false,
                    'settings' => json_encode([
                        'window_minutes' => $settings['window_minutes'] ?? self::DEFAULT_WINDOW_MINUTES,
                        'threshold'      => self::DEFAULT_THRESHOLD,
                        'filter'         => $settings['filter'] ?? [],
                    ]),
                    'updated_at' => Carbon::now(),
                ]);
        }
    }

    /**
     * The old shape back, on the old defaults. Which of them a watcher carried before is
     * not recoverable — up() overwrote them — and neither is whether it was on, so
     * `enabled` is left as it stands rather than guessed at.
     */
    public function down(): void
    {
        foreach ($this->watchersOfType(self::NEW_TYPE) as $watcher) {
            $settings = $this->settingsOf($watcher);

            DB::table('watchers')
                ->where('id', $watcher->id)
                ->update([
                    'type'     => self::OLD_TYPE,
                    'settings' => json_encode([
                        'window_minutes'   => $settings['window_minutes'] ?? self::DEFAULT_WINDOW_MINUTES,
                        'baseline_minutes' => 60,
                        'growth_percent'   => 90,
                        'filter'           => $settings['filter'] ?? [],
                    ]),
                    'updated_at' => Carbon::now(),
                ]);
        }
    }

    /**
     * Soft-deleted ones too: the row is kept so that the incidents it left behind still
     * have a name and a type behind them, and a type nothing knows about reads as nothing.
     *
     * @return iterable<object{id: int, settings: string|null}>
     */
    private function watchersOfType(string $type): iterable
    {
        /** @var iterable<object{id: int, settings: string|null}> */
        return DB::table('watchers')->where('type', $type)->get(['id', 'settings']);
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsOf(object $watcher): array
    {
        $settings = json_decode((string) ($watcher->settings ?? ''), true);

        return is_array($settings) ? $settings : [];
    }
};
