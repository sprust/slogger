PHP_FPM_SERVICE="php-fpm"
PHP_FPM_CLI="docker-compose exec $(PHP_FPM_SERVICE) "

WORKERS_SERVICE="workers"
WORKERS_CLI="docker-compose exec $(WORKERS_SERVICE) "

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
	@make rr-get-binary
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

bash-workers:
	@"$(WORKERS_CLI)"bash

art:
	@"$(PHP_FPM_CLI)"php artisan ${c}

composer:
	@docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) composer ${c}

queues-restart:
	@make art c='queue:restart'
	@make art c='cron:restart'
	@make art c='octane:roadrunner:stop'
	@make art c='octane:swoole:stop'

oa-generate:
	@make art c='oa:generate'

deploy:
	git pull
	@make composer c=i
	@make art c='migrate --force'
	@make restart

rr-get-binary:
	@"$(WORKERS_CLI)"./vendor/bin/rr get-binary
