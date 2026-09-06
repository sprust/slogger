<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fills in the ws pool's credentials, the way key:generate fills in APP_KEY.
 *
 * They cannot ship with the example: the secret signs channel subscriptions, and one
 * published in a repository signs nothing. They also cannot be left empty, because the
 * pool is on by default — WsStartCommand refuses to start without them and the ws group
 * would restart for ever. So `make setup` generates a pair, and this is that step.
 *
 * Both files are written, because the key is needed on both sides: the backend signs
 * with it, and the browser carries it in the upgrade path (/app/{key}), which puts it in
 * the panel's bundle at build time.
 */
class WsKeysGenerateCommand extends Command
{
    protected $signature = 'ws-keys-generate {--force : Replace credentials that are already set}';

    protected $description = 'Generate SCONCUR_WS_APP_KEY/SCONCUR_WS_APP_SECRET and mirror the key into frontend/.env';

    /** The app key travels in a URL path, so the alphabet has to be one that survives it. */
    private const int KEY_LENGTH = 32;

    private const int SECRET_LENGTH = 48;

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (!is_file($envPath)) {
            $this->components->error('.env is missing. Run `make env-copy` first.');

            return self::FAILURE;
        }

        $env = (string) file_get_contents($envPath);

        if (!$this->option('force') && $this->isFilled($env, 'SCONCUR_WS_APP_KEY') && $this->isFilled($env, 'SCONCUR_WS_APP_SECRET')) {
            $this->components->info('The ws credentials are already set; use --force to replace them.');

            return self::SUCCESS;
        }

        $key    = Str::random(self::KEY_LENGTH);
        $secret = Str::random(self::SECRET_LENGTH);

        file_put_contents(
            $envPath,
            $this->set($this->set($env, 'SCONCUR_WS_APP_KEY', $key), 'SCONCUR_WS_APP_SECRET', $secret)
        );

        $this->components->info('SCONCUR_WS_APP_KEY and SCONCUR_WS_APP_SECRET written to .env');

        $frontendEnvPath = base_path('frontend/.env');

        // Not a failure: the backend half is what the pool refuses to start without, and
        // a panel built without the key falls back to polling rather than breaking.
        if (!is_file($frontendEnvPath)) {
            $this->components->warn('frontend/.env is missing; SCONCUR_WS_KEY was not mirrored into it.');

            return self::SUCCESS;
        }

        file_put_contents(
            $frontendEnvPath,
            $this->set((string) file_get_contents($frontendEnvPath), 'SCONCUR_WS_KEY', $key)
        );

        $this->components->info('SCONCUR_WS_KEY written to frontend/.env');

        return self::SUCCESS;
    }

    private function isFilled(string $env, string $name): bool
    {
        return preg_match('/^' . preg_quote($name, '/') . '=(.+)$/m', $env) === 1;
    }

    /**
     * Rewrites one assignment, or appends it when the file predates the variable.
     *
     * Anchored to the start of a line: `SCONCUR_WS_KEY` is also the tail of
     * `VITE_SCONCUR_WS_KEY`, and `SCONCUR_WS_APP_KEY` the head of nothing yet — but the
     * next variable added beside them should not silently take the write.
     */
    private function set(string $env, string $name, string $value): string
    {
        $pattern = '/^' . preg_quote($name, '/') . '=.*$/m';

        if (preg_match($pattern, $env) !== 1) {
            return rtrim($env, "\n") . "\n$name=$value\n";
        }

        return (string) preg_replace($pattern, "$name=$value", $env, 1);
    }
}
