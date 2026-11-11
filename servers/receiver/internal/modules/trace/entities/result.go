package entities

// Result is what a batch came to: how many traces were written, and which were not.
type Result struct {
	Saved int
	// Failed holds, per service, the ids of the traces that could not be merged. The rest
	// of the batch was written without them.
	Failed map[int]map[string]bool
}
