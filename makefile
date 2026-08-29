MAKEFLAGS += --no-print-directory

PHP_FPM_SERVICE="php-fpm"
PHP_FPM_CLI="docker-compose exec $(PHP_FPM_SERVICE) "

WORKERS_SERVICE="workers"
WORKERS_CLI="docker-compose exec $(WORKERS_SERVICE) "

RECEIVER_SERVICE="receiver"
RECEIVER_CLI="docker-compose exec $(RECEIVER_SERVICE) "

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
	cp -i servers/receiver/.env.example servers/receiver/.env
	cp -i frontend/.env.example frontend/.env

setup:
	make env-copy
	docker-compose stop
	docker-compose down
	make build
	make up
	make composer c=install
	make art c=key:generate
	make art c="migrate --force"
	make workers-art c='queues-declare'
	make frontend-npm-i
	make frontend-npm-build
	make restart

build:
	docker-compose build

up:
	docker-compose up -d

stop:
	docker-compose stop

restart:
	make stop
	make up

bash-php-fpm:
	"$(PHP_FPM_CLI)"bash

code-analise-declare-strict-fix:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) php artisan declare-strict-fix

code-analise-stan:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) ./vendor/bin/phpstan analyse -c ./code-analyse/phpstan.neon  --memory-limit=1G

code-analise-deptrac:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) ./vendor/bin/deptrac analyse --config-file=./code-analyse/deptrac-layers.yaml

code-analise-cs-fixer-check:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) ./vendor/bin/php-cs-fixer fix --config ./code-analyse/php-cs-fixer.dist.php --dry-run --diff --verbose

code-analise-cs-fixer-fix:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) ./vendor/bin/php-cs-fixer fix --config ./code-analyse/php-cs-fixer.dist.php --verbose

code-analise:
	make code-analise-declare-strict-fix
	make code-analise-cs-fixer-check
	make code-analise-stan
	make code-analise-deptrac

test:
	"$(PHP_FPM_CLI)"php artisan test ${c}

check:
	make code-analise
	make test

bash-workers:
	"$(WORKERS_CLI)"bash

bash-receiver:
	"$(RECEIVER_CLI)"bash

bash-frontend:
	"$(FRONTEND_CLI)"sh

art:
	"$(PHP_FPM_CLI)"php artisan ${c}

workers-art:
	"$(WORKERS_CLI)"php artisan ${c}

composer:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) composer ${c}

# Composer in a throwaway container off the freshly built image, so it runs with
# the sconcur.so that matches composer.lock while the old containers keep serving.
# vendor is a bind mount, so what it writes is what the recreated containers get.
composer-fresh:
	docker-compose run --rm --no-deps -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) composer ${c}

# The cron and the index monitor used to be stopped here by name. They are tasks of the
# coroutine pool now, which the master supervises, so restarting the master restarts them
# — and calling the old commands would abort this target before it ever got that far,
# since make stops at the first non-zero exit and both were deleted with them.
workers-restart:
	make workers-art c='queues-declare'
	make workers-art c='queue:restart'
	make workers-art c='slogger:dispatcher:stop'
	make sconcur-restart

oa-generate:
	make art c='oa:generate'
	make frontend-npm-generate

# queues-declare is on the deploy path because the application's queues moved from Redis,
# which creates a queue on first use, to AMQP, which does not: publishing to a routing key
# nothing is bound to is dropped by the broker without an error, and the consumer runtime
# declares nothing of its own. A deploy that skipped it would lose jobs silently.
#
# Order matters: sconcur.so is baked into the image from composer.lock, so vendor
# and the extension have to be brought into step before any long-lived process
# starts on them. Installing from the new image first (composer-fresh) and only
# then swapping containers keeps the old ones serving until the moment they are
# replaced. `up` recreates just what the rebuild changed — php-fpm and workers —
# and leaves mysql, mongo, redis and rabbitmq running.
deploy-prod:
	git pull
	make build
	make composer-fresh c='i --no-dev'
	make up
	make workers-art c='queues-declare'
	make art c='migrate --force'
	make receiver-build
	make frontend-npm-i
	make frontend-npm-build
	make frontend-restart

deploy-dev:
	git pull
	make build
	make composer-fresh c='i'
	make up
	make workers-art c='queues-declare'
	make art c='migrate --force'
	make receiver-build
	make frontend-npm-i
	make frontend-npm-build
	make frontend-restart

frontend-npm-i:
	"$(FRONTEND_CLI)"npm i

frontend-npm-build:
	"$(FRONTEND_CLI)"npm run build

frontend-restart:
	docker-compose restart $(FRONTEND_SERVICE)

frontend-npm-generate:
	"$(FRONTEND_CLI)"npm run generate

receiver-monitor:
	make art c=receiver:monitor

receiver-build:
	docker-compose run --rm --no-deps $(RECEIVER_SERVICE) make build stats-build
	docker-compose up -d --force-recreate $(RECEIVER_SERVICE)

# require has to come first — it is what writes the version the image build reads
# from composer.lock — so --no-scripts keeps it from booting the framework on the
# new library while the old sconcur.so is still in place. package:discover then
# runs from dump-autoload against the rebuilt image.
sconcur-update:
	docker-compose up -d $(PHP_FPM_SERVICE) $(WORKERS_SERVICE)
	make composer c='require sconcur/sconcur:* --no-scripts'
	make build
	make composer-fresh c='dump-autoload'
	make up
	make sconcur-status

sconcur-restart:
	make workers-art c=sconcur:servers:master:stop

sconcur-status:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) ./vendor/bin/sconcur-status

