PHP_SERVICE="php"
PHP_CLI="docker-compose exec $(PHP_SERVICE) "

RR_DOTENV=--dotenv /app/.env
RR_YAML=-c /app/.rr.yaml
RR_COLLECTOR_YAML=-c /app/.rr.collector.yaml

env-copy:
	cp -i .env.example .env
	@make rr-default-config
	cp -i frontend/.env.example frontend/.env

setup:
	@docker-compose stop
	@docker-compose down
	@docker-compose build
	@make up
	@make composer c=install
	@make art c=key:generate
	@make art c="migrate --force"
	@make rr-get-binary
	@make restart

up:
	@docker-compose up -d

stop:
	@docker-compose stop

restart:
	@make stop
	@make up

bash-php:
	@docker-compose exec $(PHP_SERVICE) bash

art:
	@"$(PHP_CLI)"php artisan ${c}

composer:
	@docker-compose exec -e XDEBUG_MODE=off $(PHP_SERVICE) composer ${c}

queues-restart:
	@make art c='queue:restart'
	@make art c='cron:restart'
	@make rr-reset

oa-generate:
	@make art c='oa:generate'

deploy:
	git pull
	@make composer c=i
	@make art c='migrate --force'
	@make restart

rr-get-binary:
	@"$(PHP_CLI)"./vendor/bin/rr get-binary

rr-default-config:
	cp -i .rr.yaml.example .rr.yaml
	cp -i .rr.collector.yaml.example .rr.collector.yaml

rr-reset:
	@"$(PHP_CLI)"./rr reset $(RR_YAML)
	@"$(PHP_CLI)"./rr reset $(RR_COLLECTOR_YAML)

rr-stop:
	@"$(PHP_CLI)"./rr stop $(RR_YAML)

rr-workers:
	@"$(PHP_CLI)"./rr workers -i $(RR_YAML)

rr-collector-workers:
	@"$(PHP_CLI)"./rr workers -i $(RR_COLLECTOR_YAML)
