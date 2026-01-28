GOLANG_CLI="docker-compose run -it --rm --user $$(id -u):$$(id -g) golang"

bash:
	"$(GOLANG_CLI)" bash

run:
	go run ./cmd/server/main.go ${c}

build:
	CGO_ENABLED=0 GOOS=linux go build -v -a -o ./bin/receiver ./cmd/server/main.go \
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
