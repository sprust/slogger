
# Concurrency for laravel 

## Installation

```bash
php artisan vendor:publish --tag=rr-concurrency-laravel
```

### App (RR driver)

system:
```bash
php artisan vendor:publish --tag=rr-concurrency-laravel
```

.env:
```dotenv
# one of: rr, sync
RR_CONCURRENCY_DRIVER=rr
RR_CONCURRENCY_RPC_HOST=0.0.0.0
RR_CONCURRENCY_RPC_PORT=9044
RR_CONCURRENCY_KV_STORAGE_NAME=concurrency
RR_CONCURRENCY_WORKERS_NUMBER=5
RR_CONCURRENCY_WORKERS_MAX_NUMBER=20
```

.gitignore:
```gitignore
rr
.rr.yaml
.pid
```

supervisor example:
```
[program:rr-server]
command= /app./rr serve --dotenv /app/.env -c /app/.rr-concurrency.yaml
stdout_logfile=/var/log/supervisor/rr-server-out.log
stderr_logfile=/var/log/supervisor/rr-server-err.log
autostart=true
autorestart=true
startsecs=0

[program:rr-jobs-monitor]
command= php /app/artisan rr-concurrency:monitor
stdout_logfile=/var/log/supervisor/rr-jobs-monitor-out.log
stderr_logfile=/var/log/supervisor/rr-jobs-monitor-err.log
autostart=true
autorestart=true
startsecs=0
```
