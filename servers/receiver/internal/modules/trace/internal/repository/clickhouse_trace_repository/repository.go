package clickhouse_trace_repository

import (
	"bufio"
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"slogger_receiver/internal/modules/trace/entities"
	"slogger_receiver/internal/platform/clickhouse"
	"slogger_receiver/pkg/foundation/errs"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/klauspost/compress/zstd"
)

// findExistingChunk keeps the keys of one query well under the 1 MiB URL limit.
const findExistingChunk = 1000

type storedRow struct {
	ServiceId     int      `json:"sid"`
	TraceId       string   `json:"tid"`
	LoggedAt      string   `json:"lat"`
	ParentTraceId string   `json:"ptid"`
	Type          string   `json:"tp"`
	Status        string   `json:"st"`
	Tags          []string `json:"tgs"`
	RawData       string   `json:"dt_raw"`
	Duration      *float64 `json:"dur"`
	Memory        *float64 `json:"mem"`
	Cpu           *float64 `json:"cpu"`
	Pid           *uint32  `json:"pid"`
	CreatedAt     string   `json:"cat"`
}

var instance *Repository
var once sync.Once

func Get() *Repository {
	once.Do(func() {
		instance = &Repository{
			conn: clickhouse.Get(),
		}

	})

	return instance
}

type Repository struct {
	conn *clickhouse.Connection
}

// FindExisting reads the stored version of each trace of a batch in one query.
//
// FINAL, because a trace written twice sits in two parts until they are merged, and the
// merge is only right on the latest version. The keys name the partition and the sorting
// key, so the query reads only the granules those traces are in.
func (r *Repository) FindExisting(ctx context.Context, keys []entities.Key) (map[entities.Key]entities.StoredTrace, error) {
	result := make(map[entities.Key]entities.StoredTrace, len(keys))

	// The keys travel in the URL, which ClickHouse caps at 1 MiB.
	for start := 0; start < len(keys); start += findExistingChunk {
		if err := r.findExisting(ctx, keys[start:min(start+findExistingChunk, len(keys))], result); err != nil {
			return nil, err
		}
	}

	return result, nil
}

func (r *Repository) findExisting(ctx context.Context, keys []entities.Key, result map[entities.Key]entities.StoredTrace) error {
	query := "SELECT sid, tid, lat, ptid, tp, st, tgs, dt_raw, dur, mem, cpu, pid, cat FROM traces FINAL " +
		"WHERE (sid, lat, tid) IN {keys:Array(Tuple(UInt32, DateTime64(6, 'UTC'), String))} " +
		"FORMAT JSONEachRow"

	body, err := r.conn.Send(
		ctx,
		map[string]string{"param_keys": formatKeys(keys)},
		strings.NewReader(query),
		"",
	)

	if err != nil {
		return errs.Err(err)
	}

	defer body.Close()

	scanner := bufio.NewScanner(body)
	scanner.Buffer(make([]byte, 0, 64*1024), 64*1024*1024)

	for scanner.Scan() {
		line := scanner.Bytes()

		if len(line) == 0 {
			continue
		}

		var row storedRow

		if err := json.Unmarshal(line, &row); err != nil {
			return errs.Err(fmt.Errorf("clickhouse answered %q: %w", truncate(string(line)), err))
		}

		loggedAt, err := time.Parse(entities.TimeLayout, row.LoggedAt)

		if err != nil {
			return errs.Err(err)
		}

		createdAt, err := time.Parse(entities.TimeLayout, row.CreatedAt)

		if err != nil {
			return errs.Err(err)
		}

		key := entities.Key{ServiceId: row.ServiceId, LoggedAtMicro: loggedAt.UnixMicro(), TraceId: row.TraceId}

		result[key] = entities.StoredTrace{
			ParentTraceId: row.ParentTraceId,
			Type:          row.Type,
			Status:        row.Status,
			Tags:          row.Tags,
			RawData:       row.RawData,
			Duration:      row.Duration,
			Memory:        row.Memory,
			Cpu:           row.Cpu,
			Pid:           row.Pid,
			CreatedAt:     createdAt.UTC(),
		}
	}

	if err := scanner.Err(); err != nil {
		return errs.Err(err)
	}

	return nil
}

// Insert writes the rows in one INSERT. Synchronous: the next pass of the transporter
// reads what this one wrote, so the rows have to be there when this returns.
func (r *Repository) Insert(ctx context.Context, rows []entities.Row) error {
	if len(rows) == 0 {
		return nil
	}

	plain := make([]byte, 0, len(rows)*1024)

	for index := range rows {
		var err error

		if plain, err = appendRow(plain, &rows[index]); err != nil {
			return errs.Err(err)
		}
	}

	compressor, err := zstdEncoder()

	if err != nil {
		return errs.Err(err)
	}

	// zstd: a third of the CPU gzip took for a smaller body, and the same insert time in ClickHouse
	payload := compressor.EncodeAll(plain, make([]byte, 0, len(plain)/6))

	body, err := r.conn.Send(
		ctx,
		map[string]string{
			"query": "INSERT INTO traces FORMAT JSONEachRow",
			// a key with a dot beside the same path nested (`a.b` and `a: {b}`) would refuse
			// the whole batch; dt keeps the first, dt_raw keeps both
			"type_json_skip_duplicated_paths": "1",
			// a string that looks like a date stays a string, or text filters would miss it
			"input_format_try_infer_dates":     "0",
			"input_format_try_infer_datetimes": "0",
		},
		bytes.NewReader(payload),
		"zstd",
	)

	if err != nil {
		return errs.Err(err)
	}

	_, _ = io.Copy(io.Discard, body)

	return body.Close()
}

var zstdOnce sync.Once
var zstdShared *zstd.Encoder
var zstdErr error

// zstdEncoder is one encoder for every insert: it holds its buffers between them.
func zstdEncoder() (*zstd.Encoder, error) {
	zstdOnce.Do(func() {
		zstdShared, zstdErr = zstd.NewWriter(nil, zstd.WithEncoderLevel(zstd.SpeedFastest), zstd.WithEncoderConcurrency(1))
	})

	return zstdShared, zstdErr
}

// formatKeys writes the keys as an array literal of tuples, the text a
// `{keys:Array(Tuple(...))}` parameter is parsed from.
func formatKeys(keys []entities.Key) string {
	builder := strings.Builder{}
	builder.WriteByte('[')

	for index, key := range keys {
		if index > 0 {
			builder.WriteByte(',')
		}

		builder.WriteByte('(')
		builder.WriteString(strconv.Itoa(key.ServiceId))
		builder.WriteString(",'")
		builder.WriteString(time.UnixMicro(key.LoggedAtMicro).UTC().Format(entities.TimeLayout))
		builder.WriteString("',")
		builder.WriteString(quote(key.TraceId))
		builder.WriteByte(')')
	}

	builder.WriteByte(']')

	return builder.String()
}

func quote(value string) string {
	replacer := strings.NewReplacer(`\`, `\\`, `'`, `\'`, "\n", `\n`, "\t", `\t`, "\r", `\r`)

	return "'" + replacer.Replace(value) + "'"
}

func truncate(value string) string {
	if len(value) > 500 {
		return value[:500] + "..."
	}

	return value
}
