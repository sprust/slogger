PHP_SERVICE="php"
PHP_CLI="docker-compose exec $(PHP_SERVICE) "

RR_DOTENV=--dotenv /app/.env
RR_YAML=-c /app/.rr.yaml

setup:
	@docker-compose stop
	@docker-compose down
	@docker-compose build
	@make up
	@make composer c=install
	@make art c=key:generate
	@make rr-init
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
	@make rr-stop

rr-init:
	make rr-get-binary
	make rr-default-config
	@make art c='vendor:publish --tag=ifksco-roadrunner-laravel'

rr-get-binary:
	@"$(PHP_CLI)"./vendor/bin/rr get-binary

rr-default-config:
	cp -i packages/ifksco/roadrunner-laravel/config/.rr.yaml.example .rr.yaml

rr-start:
	@"$(PHP_CLI)"./rr serve $(RR_DOTENV) $(RR_YAML)

rr-start-php:
	@"$(PHP_CLI)"./rr serve $(RR_DOTENV) $(RR_YAML)

rr-reset:
	@"$(PHP_CLI)"./rr reset $(RR_YAML)

rr-stop:
	@"$(PHP_CLI)"./rr stop $(RR_YAML)

rr-workers:
	@"$(PHP_CLI)"./rr workers -i $(RR_YAML)
