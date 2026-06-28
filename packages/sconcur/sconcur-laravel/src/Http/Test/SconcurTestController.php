<?php

declare(strict_types=1);

namespace SConcur\Laravel\Http\Test;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use SConcur\Features\Sleeper\Sleeper;
use Throwable;

/**
 * Diagnostic endpoints for stressing the coroutine-scoped stack under real
 * concurrency. Enabled only via config('sconcur.test_routes').
 */
class SconcurTestController
{
    private const string TABLE = 'sconcur_test';

    /**
     * Mutate per-request state (locale + config overlay), yield the coroutine via
     * an async sleep so siblings run, then assert the values survived unchanged.
     * Cross-talk would show another request's tag here.
     */
    public function isolation(Request $request): JsonResponse
    {
        $tag = (string) $request->query('tag', 'none');
        $ms  = (int) $request->query('ms', 50);

        App::setLocale($tag);                 // AsyncTranslator: per-coroutine locale
        config(['sconcur_test.tag' => $tag]); // AsyncConfig: per-coroutine overlay

        Sleeper::usleep($ms * 1000);          // yield — siblings run while we sleep

        $transLocale = App::make('translator')->getLocale();
        $configTag   = (string) config('sconcur_test.tag');
        $requestTag  = (string) $request->query('tag', 'none');

        return response()->json([
            'tag'          => $tag,
            'request_tag'  => $requestTag,
            'trans_locale' => $transLocale,
            'config_tag'   => $configTag,
            'route_uri'    => $request->route()?->uri(),
            'isolated'     => $transLocale === $tag && $configTag === $tag && $requestTag === $tag,
            'worker_pid'   => getmypid(),
        ]);
    }

    /** MongoDB through SConcur (the op yields the coroutine); checks request isolation around it. */
    public function mongo(Request $request): JsonResponse
    {
        $tag   = (string) $request->query('tag', 'none');
        $error = null;
        $count = null;

        try {
            $conn = DB::connection('mongodb.logs');
            $conn->table(self::TABLE)->insert(['tag' => $tag]);
            $count = $conn->table(self::TABLE)->where('tag', $tag)->count();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return response()->json([
            'tag'         => $tag,
            'request_tag' => (string) $request->query('tag', 'none'),
            'mongo_count' => $count,
            'error'       => $error,
        ]);
    }

    /** A (possibly nested) MySQL transaction with NO async yield inside — must be safe under concurrency. */
    public function pdoTx(Request $request): JsonResponse
    {
        $this->ensureTable();

        $id = (int) $request->query('id', 0);

        DB::transaction(function () use ($id): void {
            DB::table(self::TABLE)->insert(['tag' => 'tx-' . $id, 'req' => $id]);

            DB::transaction(function () use ($id): void {
                DB::table(self::TABLE)->insert(['tag' => 'tx-nested-' . $id, 'req' => $id]);
            });
        });

        return response()->json([
            'id'    => $id,
            'rows'  => DB::table(self::TABLE)->where('req', $id)->count(),
            'level' => DB::connection()->transactionLevel(),
        ]);
    }

    /**
     * ANTIPATTERN demo: a SConcur async yield (Sleeper) INSIDE an open MySQL
     * transaction. The physical PDO is shared across coroutines, so concurrent
     * calls corrupt each other's transaction — no counter-scoping can fix that.
     */
    public function pdoTxAwait(Request $request): JsonResponse
    {
        $this->ensureTable();

        $id    = (int) $request->query('id', 0);
        $error = null;

        try {
            DB::beginTransaction();
            DB::table(self::TABLE)->insert(['tag' => 'await-a-' . $id, 'req' => $id]);

            Sleeper::usleep(80 * 1000); // yield while holding the transaction

            DB::table(self::TABLE)->insert(['tag' => 'await-b-' . $id, 'req' => $id]);
            DB::commit();
        } catch (Throwable $e) {
            try {
                DB::rollBack();
            } catch (Throwable) {
                // connection may already be in a broken state
            }

            $error = $e->getMessage();
        }

        return response()->json([
            'id'    => $id,
            'error' => $error,
        ]);
    }

    private function ensureTable(): void
    {
        DB::statement(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE
            . ' (id BIGINT AUTO_INCREMENT PRIMARY KEY, tag VARCHAR(64) NULL, req INT NULL)'
        );
    }
}
