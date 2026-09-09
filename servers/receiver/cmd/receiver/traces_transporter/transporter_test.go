package traces_transporter

import "testing"

// The flag is the only thing that gets the loop in Run to come out, and coming out is
// what writes the watchers' last buckets and lets main stop without waiting out its
// deadline.
//
// It used to be lowered again by a `defer` on stop itself, which left it raised for the
// length of one log line — never long enough for a loop whose every turn contains a
// database round trip to see it.
func TestStopLeavesTheClosingFlagRaised(t *testing.T) {
	transporter := &Transporter{}

	transporter.stop()

	if !transporter.closing.Load() {
		t.Fatal("stop() left the transporter looking like it was still running")
	}
}
