# SLogger

## Installation

### Copy env files
```bash
make env-copy
```

### Set up environment variables. Below some important of them.
.env
```dotenv
APP_ENV=production# or local
APP_DEBUG=false# true for local

DOCKER_USER_ID=1000# see comments in .env
DOCKER_GROUP_ID=1000# see comments in .env

FRONTEND_DOCKER_COMMAND=${FRONTEND_DOCKER_SERVER_COMMAND}# or ${FRONTEND_DOCKER_LOCAL_COMMAND}
FRONTEND_DOCKER_PORT=3075# external port for web panel

RR_HTTP_WORKERS_QUANTITY=5# for web panel
RR_COLLECTOR_HTTP_PORT=9021
RR_COLLECTOR_HTTP_WORKERS_QUANTITY=10# for collector

QUEUE_TRACES_CREATING_QUANTITY=10# for collector jobs
```
frontend/.env
```dotenv
BACKEND_URL=https://localhost:10021# see port in .env.APP_PORT
```

## Setup
```bash
make setup
```

## User creating
```bash
make art c=user:create
```

## Service creating
```bash
make art c=service:create
```

## Open-api scheme
```text
storage/api/json-schemes/traces-api-openapi-scheme.json
```
