<?php

use App\Services\Clickhouse\ClickhouseClient;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    // DDL of ClickHouse is not transactional, and the statements go to ClickHouse, not to
    // the connection the migrator would wrap in a transaction.
    public $withinTransaction = false;

    public function up(): void
    {
        $client = app(ClickhouseClient::class);

        // One row per trace (sid, lat, tid). The receiver merges a trace's create and update
        // before writing, and ReplacingMergeTree keeps the version with the latest uat.
        // lat is the same in both: an update carries plat, the lat of its create, so both
        // land in one partition and share the sorting key.
        //
        // dt: every dynamic path of a JSON column is a subcolumn of its own, a few files each
        // in a wide part, and a merge holds buffers for every one of them. The cache watcher
        // files an entry under its key (`cache.<key>.value`), and the keys of an application's
        // cache — as many as it has users, products or carts — made up to 1024 paths per part:
        // twelve thousand files in a part of twenty megabytes, and a gigabyte of memory to
        // merge it. Paths beyond 256 go to the shared data of the column and can still be
        // filtered on, only slower; cache entries stay in dt_raw, and their key is a tag.
        $client->command(
            sql: <<<'SQL'
                CREATE TABLE IF NOT EXISTS traces
                (
                    sid    UInt32,
                    tid    String,
                    ptid   String,
                    tp     LowCardinality(String),
                    st     LowCardinality(String),
                    tgs    Array(LowCardinality(String)),
                    dt     JSON(max_dynamic_paths = 256, SKIP REGEXP '^cache\\.'),
                    dt_raw String CODEC(ZSTD(3)),
                    dur    Nullable(Float64),
                    mem    Nullable(Float64),
                    cpu    Nullable(Float64),
                    lat    DateTime64(6, 'UTC'),
                    cat    DateTime64(6, 'UTC'),
                    uat    DateTime64(6, 'UTC'),

                    INDEX tid_bf tid TYPE bloom_filter(0.01) GRANULARITY 1,
                    INDEX ptid_bf ptid TYPE bloom_filter(0.01) GRANULARITY 1,
                    INDEX tgs_bf tgs TYPE bloom_filter(0.01) GRANULARITY 1,
                    INDEX tp_set tp TYPE set(1000) GRANULARITY 4,
                    INDEX st_set st TYPE set(100) GRANULARITY 4,
                    INDEX dur_mm dur TYPE minmax GRANULARITY 4
                )
                ENGINE = ReplacingMergeTree(uat)
                PARTITION BY toStartOfHour(lat)
                ORDER BY (sid, lat, tid)
                SQL,
            queryIdPrefix: 'migrate'
        );
    }

    public function down(): void
    {
        app(ClickhouseClient::class)->command(
            sql: 'DROP TABLE IF EXISTS traces',
            queryIdPrefix: 'migrate'
        );
    }
};
