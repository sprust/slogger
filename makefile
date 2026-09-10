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
	make ws-keys-generate
	make art c="migrate --force"
	make queues-declare
	make frontend-npm-i
	make frontend-npm-build
	make restart

build:
	docker-compose build

up:
	docker-compose up -d

stop:
	docker-compose stop

# queues-declare belongs here for the same reason it belongs in setup and deploy: the
# broker holds the topology, and a recreated one holds nothing. `hostname` on the
# rabbitmq service keeps the node from changing identity, so this is now a no-op on a
# broker that kept its data — and still the thing that saves a broker that did not.
restart:
	make stop
	make up
	make queues-declare

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

# Every setup and deploy runs this, because the application's queues moved from Redis,
# which creates a queue on first use, to AMQP, which does not: publishing to a routing key
# nothing is bound to is dropped by the broker without an error, and the consumer runtime
# declares nothing of its own. A path that skipped it would lose jobs silently, and a
# consumer pool started before it spins on 404 instead of reading.
queues-declare:
	make workers-art c='queues-declare'

# The ws pool is on by default and refuses to start without these, so setup and both
# deploys generate a pair. Existing credentials are kept unless c=--force is passed.
#
# `run`, not `exec`, for the same reason as composer-fresh: the deploys call this before
# `up`, so that the workers come up with the key already in place instead of restarting
# until somebody notices.
ws-keys-generate:
	docker-compose run --rm --no-deps -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) php artisan ws-keys-generate ${c}

composer:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) composer ${c}

# Composer in a throwaway container off the freshly built image, so it runs with
# the sconcur.so that matches composer.lock while the old containers keep serving.
# vendor is a bind mount, so what it writes is what the recreated containers get.
composer-fresh:
	docker-compose run --rm --no-deps -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) composer ${c}

# Rolling reload, not a stop: the workers still listening keep the port served while the
# others are replaced. A new extension or library reaches the workers but not the master
# above them — that one needs sconcur-restart.
workers-restart:
	make queues-declare
	make sconcur-reload

oa-generate:
	make art c='oa:generate'
	make frontend-npm-generate

# Order matters: sconcur.so is baked into the image from composer.lock, so vendor
# and the extension have to be brought into step before any long-lived process
# starts on them. Installing from the new image first (composer-fresh) and only
# then swapping containers keeps the old ones serving until the moment they are
# replaced. `up` recreates only what the rebuild changed and leaves mysql, mongo,
# redis and rabbitmq running.
#
# The application code is a bind mount, so an ordinary commit changes no image and
# `up` recreates nothing: the sconcur workers keep serving the classes they already
# loaded. sconcur-reload is what carries the new code to them, and it comes after
# the migration so the fresh workers start on the schema they expect.
deploy-prod:
	git pull
	make build
	make composer-fresh c='i --no-dev'
	make ws-keys-generate
	make up
	make queues-declare
	make art c='migrate --force'
	make sconcur-wait
	make sconcur-reload
	make receiver-build
	make frontend-npm-i
	make frontend-npm-build
	make frontend-restart

deploy-dev:
	git pull
	make build
	make composer-fresh c='i'
	make ws-keys-generate
	make up
	make queues-declare
	make art c='migrate --force'
	make sconcur-wait
	make sconcur-reload
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

# sconcur/sconcur is not required directly: sconcur/laravel pins it to an exact
# version, because the .so and the PHP side cross a protocol boundary that changes
# with the version. So the bridge is what gets updated, and the library follows it.
#
# The update has to come first — it is what writes the version the image build reads
# from composer.lock — so --no-scripts keeps it from booting the framework on the
# new library while the old sconcur.so is still in place. package:discover then
# runs from dump-autoload against the rebuilt image.
sconcur-update:
	docker-compose up -d $(PHP_FPM_SERVICE) $(WORKERS_SERVICE)
	make composer c='update sconcur/laravel --with-all-dependencies --no-scripts'
	make build
	make composer-fresh c='dump-autoload'
	make up
	make sconcur-status

# Waits until the master holds its lock, for the deploys to lean on.
#
# `up` returns when the container is up, not when the master inside it has booted and
# taken the lock — supervisor starts it, and a build that changed the image means `up`
# recreated the container. A deploy then reached sconcur-reload before the master
# existed, got `not running` with exit 3, and died there with four steps still to run.
# Which is the one case where there was nothing to reload: workers that have only just
# started are already on the new code.
#
# master:status is what to ask — it answers 0 running, 3 stopped, and decides by the lock
# rather than by a pid in the state file, so a stale file cannot fool it. The wait is not
# folded into sconcur-reload because that one is also typed by hand, where "not running"
# is the answer somebody wants immediately rather than in a minute.
SCONCUR_MASTER_WAIT_SECONDS ?= 60

sconcur-wait:
	@echo "waiting for the sconcur master"; \
	waited=0; \
	until "$(WORKERS_CLI)"php artisan sconcur:servers:master:status >/dev/null 2>&1; do \
		waited=$$((waited + 1)); \
		if [ $$waited -ge $(SCONCUR_MASTER_WAIT_SECONDS) ]; then \
			echo "the sconcur master did not come up in $(SCONCUR_MASTER_WAIT_SECONDS)s"; \
			"$(WORKERS_CLI)"php artisan sconcur:servers:master:status; \
			exit 1; \
		fi; \
		sleep 1; \
	done

# Fresh worker processes on the current code and config; the master keeps running.
sconcur-reload:
	make workers-art c=sconcur:servers:master:reload

# Takes the master down too, leaving the port unserved until the supervisor starts it.
sconcur-restart:
	make workers-art c=sconcur:servers:master:stop

sconcur-status:
	docker-compose exec -e XDEBUG_MODE=off $(PHP_FPM_SERVICE) ./vendor/bin/sconcur-status

