PHP_FPM_SERVICE="php-fpm"
PHP_FPM_CLI="docker-compose exec $(PHP_FPM_SERVICE) "

WORKERS_SERVICE="workers"
WORKERS_CLI="docker-compose exec $(WORKERS_SERVICE) "

FRONTEND_SERVICE="frontend"
FRONTEND_CLI="docker-compose run --rm $(FRONTEND_SERVICE) "

ifneq (,$(wildcard ./.env))
    include .env
    export
else
    include .env.example
    export
endif

env-copy:
	cp -i .env.example .env
	cp -i frontend/.env.example frontend/.env

setup:
	@docker-compose stop
	@docker-compose down
	@docker-compose build
	@make up
	@make composer c=install
	@make art c=key:generate
	@make art c="migrate --force"
	@make rabbitmq-queues-declare
	@make rr-get-binary
	@make frontend-npm-i
	@make frontend-npm-build
	@make restart

up:
	@docker-compose up -d

stop:
	@docker-compose stop

restart:
	@make stop
	@make up

bash-php-fpm:
	@"$(PHP_FPM_CLI)"bash

code-analise-stan:
	@"$(WORKERS_CLI)"./vendor/bin/phpstan analyse app -c ./code-analyse/phpstan.neon  --memory-limit=1G

code-analise-deptrac:
	@"$(WORKERS_CLI)"./vendor/bin/deptrac analyse --config-file=./code-analyse/deptrac-layers.yaml

code-analise:
	make code-analise-stan
	make code-analise-deptrac

bash-workers:
	@"$(WORKERS_CLI)"bash

bash-frontend:
	@"$(FRONTEND_CLI)"sh

art:
	@"$(PHP_FPM_CLI)"php artisan ${c}

art-workers:
	@"$(WORKERS_CLI)"php artisan ${c}

composer:
	@docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) composer ${c}

workers-restart:
	@make art-workers c='queue:restart'
	@make art-workers c='cron:restart'
	@make art-workers c='octane:roadrunner:reload'
	@make art-workers c='octane:swoole:reload'
	@make art-workers c='rr-monitor:stop grpc'
	@make art-workers c='rr-monitor:stop jobs'
	@make art-workers c='trace-dynamic-indexes:monitor:stop'

octane-stop:
	@make art-workers c='octane:roadrunner:stop'
	@make art-workers c='octane:swoole:stop'

oa-generate:
	@make art c='oa:generate'
	@make frontend-npm-generate

deploy-prod:
	git pull
	@make composer c='i --no-dev'
	@make art c='migrate --force'
	@make workers-restart
	@make frontend-npm-i
	@make frontend-npm-build
	@docker-compose restart $(FRONTEND_SERVICE)

deploy-dev:
	git pull
	@make composer c='i'
	@make art c='migrate --force'
	@make frontend-npm-i
	@make frontend-npm-build
	@make restart

rr-get-binary:
	@"$(WORKERS_CLI)"./vendor/bin/rr get-binary

rr-workers:
	@"$(WORKERS_CLI)"./rr workers -i -o rpc.listen=tcp://$(OCTANE_RR_RPC_HOST):$(OCTANE_RR_RPC_PORT) ${p}

protoc-load:
	@"$(WORKERS_CLI)"./vendor/bin/rr download-protoc-binary

protoc-compile:
	@"$(WORKERS_CLI)"protoc --plugin=protoc-gen-php-grpc \
		--php_out=./packages/slogger/grpc/generated \
		--php-grpc_out=./packages/slogger/grpc/generated \
		./packages/slogger/grpc/proto/collector.proto

frontend-npm-i:
	@"$(FRONTEND_CLI)"npm i

frontend-npm-build:
	@"$(FRONTEND_CLI)"npm run build

frontend-npm-generate:
	@"$(FRONTEND_CLI)"npm run generate

rabbitmq-queues-declare:
	@make art c="rabbitmq:queue-declare $(QUEUE_TRACES_CREATING_NAME)"
