// traces-migrate moves the traces of the MongoDB `tracesPeriodic` database into the ClickHouse
// `traces` table, newest hour first, and deletes each batch from MongoDB once ClickHouse has
// it, so that a run stopped halfway goes on from where it was:
//
//	make traces-migrate-mongo-clickhouse
package main

import (
	"context"
	"errors"
	"flag"
	"fmt"
	"log"
	"os"
	"os/signal"
	"regexp"
	"slices"
	"slogger_receiver/internal/repositories/clickhouse_trace_repository"
	"strings"
	"syscall"
	"time"

	"github.com/joho/godotenv"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)

// collectionPattern is the name of an hourly collection: traces_2026_09_30_10_11.
var collectionPattern = regexp.MustCompile(`^traces_\d{4}_\d{2}_\d{2}_\d{2}_\d{2}$`)

const viewName = "_traceTreesView"

type migrator struct {
	database *mongo.Database
	store    *clickhouse_trace_repository.Repository
	batch    int
	progress progress
}

// progress is the run as a whole, for the line each batch prints.
type progress struct {
	started     time.Time
	collection  int
	collections int
	planned     int64
	moved       int64
}

func main() {
	batch := flag.Int("batch", 5000, "traces per ClickHouse insert")
	flag.Parse()

	if err := godotenv.Load(); err != nil {
		log.Fatalf(".env: %v", err)
	}

	ctx, cancel := signal.NotifyContext(context.Background(), os.Interrupt, syscall.SIGTERM)
	defer cancel()

	client, err := mongo.Connect(ctx, options.Client().ApplyURI(fmt.Sprintf(
		"mongodb://%s:%s@%s:%s",
		os.Getenv("MONGODB_USERNAME"),
		os.Getenv("MONGODB_PASSWORD"),
		os.Getenv("MONGODB_HOST"),
		os.Getenv("MONGODB_PORT"),
	)))

	if err != nil {
		log.Fatalf("mongo: %v", err)
	}

	defer func() {
		_ = client.Disconnect(context.Background())
	}()

	databaseName := os.Getenv("MONGODB_DB_PERIODIC_TRACES")

	if databaseName == "" {
		databaseName = "tracesPeriodic"
	}

	m := &migrator{
		database: client.Database(databaseName),
		store:    clickhouse_trace_repository.Get(),
		batch:    *batch,
	}

	if err := m.run(ctx); err != nil {
		log.Fatalf("stopped: %v", err)
	}
}

func (m *migrator) run(ctx context.Context) error {
	names, err := m.database.ListCollectionNames(ctx, bson.M{})

	if err != nil {
		return err
	}

	collections := make([]string, 0, len(names))

	for _, name := range names {
		if collectionPattern.MatchString(name) {
			collections = append(collections, name)
		}
	}

	// newest first: those are the traces an update arriving now may still be looking for
	slices.Sort(collections)
	slices.Reverse(collections)

	// estimated counts: the run only needs them for its progress line
	for _, name := range collections {
		count, err := m.database.Collection(name).EstimatedDocumentCount(ctx)

		if err != nil {
			return err
		}

		m.progress.planned += count
	}

	log.Printf("database %s: %d hourly collections, about %s traces", m.database.Name(), len(collections), short(m.progress.planned))

	m.progress.started = time.Now()
	m.progress.collections = len(collections)

	started := m.progress.started
	left := 0
	total := 0

	for index, name := range collections {
		if ctx.Err() != nil {
			return ctx.Err()
		}

		m.progress.collection = index + 1

		moved, skipped, err := m.migrateCollection(ctx, name)

		total += moved

		if err != nil {
			return fmt.Errorf("%s: %w", name, err)
		}

		left += skipped
	}

	if err := m.dropIfDone(ctx); err != nil {
		return err
	}

	log.Printf("done: %d traces moved in %s, %d left unreadable in MongoDB", total, time.Since(started).Round(time.Second), left)

	return nil
}

// dropIfDone drops the database once nothing but the view over its hours is left in it.
func (m *migrator) dropIfDone(ctx context.Context) error {
	names, err := m.database.ListCollectionNames(ctx, bson.M{})

	if err != nil {
		return err
	}

	for _, name := range names {
		if name != viewName && !strings.HasPrefix(name, "system.") {
			log.Printf("database %s kept: %s is still in it", m.database.Name(), name)

			return nil
		}
	}

	if len(names) == 0 {
		return nil
	}

	log.Printf("database %s dropped", m.database.Name())

	return m.database.Drop(ctx)
}

// migrateCollection moves one hour and drops its collection, unless some of its documents
// could not be read: those stay where they are, and the run says so.
func (m *migrator) migrateCollection(ctx context.Context, name string) (int, int, error) {
	collection := m.database.Collection(name)

	count, err := collection.EstimatedDocumentCount(ctx)

	if err != nil {
		return 0, 0, err
	}

	moved := 0
	skipped := 0

	var lastId interface{}

	for ctx.Err() == nil {
		filter := bson.M{}

		if lastId != nil {
			filter["_id"] = bson.M{"$gt": lastId}
		}

		cursor, err := collection.Find(
			ctx,
			filter,
			options.Find().SetSort(bson.D{{Key: "_id", Value: 1}}).SetLimit(int64(m.batch)),
		)

		if err != nil {
			return moved, skipped, err
		}

		rows := make([]clickhouse_trace_repository.Row, 0, m.batch)
		ids := make([]interface{}, 0, m.batch)
		now := time.Now().UTC()
		read := 0

		for cursor.Next(ctx) {
			read++

			raw := bson.Raw(append([]byte(nil), cursor.Current...))

			var doc bson.M

			if err := bson.Unmarshal(raw, &doc); err != nil {
				return moved, skipped, err
			}

			lastId = doc["_id"]

			row, err := toRow(raw, doc, now)

			if err != nil {
				skipped++

				log.Printf("%s: %v left in MongoDB: %v", name, doc["_id"], err)

				continue
			}

			rows = append(rows, row)
			ids = append(ids, doc["_id"])
		}

		cursorErr := cursor.Err()

		_ = cursor.Close(ctx)

		if cursorErr != nil {
			return moved, skipped, cursorErr
		}

		// read after the last id seen, so a document left unreadable is not read again
		if read == 0 {
			break
		}

		if len(rows) > 0 {
			// Not cancelled by a stop: a batch is inserted and deleted whole.
			batchCtx := context.WithoutCancel(ctx)

			if err := m.insert(batchCtx, rows); err != nil {
				return moved, skipped, err
			}

			if _, err := collection.DeleteMany(batchCtx, bson.M{"_id": bson.M{"$in": ids}}); err != nil {
				return moved, skipped, err
			}

			moved += len(rows)
			m.progress.moved += int64(len(rows))

			log.Printf("%s", m.progress.line(name, moved, count))
		}
	}

	if ctx.Err() != nil {
		return moved, skipped, ctx.Err()
	}

	if skipped > 0 {
		log.Printf("%s: kept, %d documents could not be read", name, skipped)

		return moved, skipped, nil
	}

	if err := collection.Drop(ctx); err != nil {
		return moved, skipped, err
	}

	return moved, 0, nil
}

// line says where the run is: [12/72] traces_…: 45000/210000, total 1.2M/14.8M, 6100/s, ~42m left
func (p progress) line(name string, moved int, count int64) string {
	elapsed := time.Since(p.started).Seconds()
	rate := 0.0

	if elapsed > 0 {
		rate = float64(p.moved) / elapsed
	}

	eta := "?"

	if rate > 0 {
		eta = (time.Duration(float64(max(p.planned-p.moved, 0))/rate) * time.Second).Round(time.Second).String()
	}

	return fmt.Sprintf(
		"[%d/%d] %s: %d/%d, total %s/%s, %.0f/s, ~%s left",
		p.collection, p.collections, name, moved, count, short(p.moved), short(p.planned), rate, eta,
	)
}

// short writes a count the way a person reads it: 950, 12.4K, 1.2M.
func short(count int64) string {
	switch {
	case count >= 1_000_000:
		return fmt.Sprintf("%.1fM", float64(count)/1_000_000)
	case count >= 1_000:
		return fmt.Sprintf("%.1fK", float64(count)/1_000)
	default:
		return fmt.Sprintf("%d", count)
	}
}

// insert tries a batch again while ClickHouse is restarting or busy; any other failure stops
// the run with the batch still in MongoDB.
func (m *migrator) insert(ctx context.Context, rows []clickhouse_trace_repository.Row) error {
	var err error

	for attempt := 1; attempt <= 10; attempt++ {
		if err = m.store.Insert(ctx, rows); err == nil {
			return nil
		}

		var queryError *clickhouse_trace_repository.QueryError

		if errors.As(err, &queryError) && !slices.Contains([]int{159, 202, 209, 210, 241, 242, 252}, queryError.Code) {
			return err
		}

		log.Printf("insert failed, attempt %d: %v", attempt, err)

		time.Sleep(time.Duration(attempt) * time.Second)
	}

	return err
}
