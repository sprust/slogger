-- One row per trace (sid, lat, tid). The receiver merges a trace's create and update
-- before writing, and ReplacingMergeTree keeps the version with the latest uat.
-- lat is the same in both: an update carries plat, the lat of its create, so both land
-- in one partition and share the sorting key.
CREATE TABLE IF NOT EXISTS traces
(
    sid    UInt32,
    tid    String,
    ptid   String,
    tp     LowCardinality(String),
    st     LowCardinality(String),
    tgs    Array(LowCardinality(String)),
    dt     JSON(max_dynamic_paths = 1024),
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
