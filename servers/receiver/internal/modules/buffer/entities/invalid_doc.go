package entities

import "go.mongodb.org/mongo-driver/bson"

// InvalidDoc is a buffer document the transporter could not act on, on its way out of the
// buffer. Reason is recorded beside it so the invalid buffer says why it is there —
// whether the format changed under us or a save kept failing.
type InvalidDoc struct {
	Doc    bson.M
	Reason string
}
