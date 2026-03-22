run:
	go run ./cmd/receiver/main.go ${c}

build:
	CGO_ENABLED=0 GOOS=linux go build -v -a -o ./bin/receiver ./cmd/receiver/main.go \
		&& chmod +x ./bin/receiver

bin-start:
	./bin/receiver ${c}

stats-run:
	go run ./cmd/stats/main.go

stats-build:
	CGO_ENABLED=0 GOOS=linux go build -v -a -o ./bin/stats ./cmd/stats/main.go \
		&& chmod +x ./bin/stats

stats-bin-start:
	./bin/stats ${c}
