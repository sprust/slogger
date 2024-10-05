
# Parallel for laravel 

## Installation

```bash
php artisan vendor:publish --tag=rr-parallel-laravel
```

### App (RR driver)

system:
```bash
php artisan vendor:publish --tag=rr-parallel-laravel
```

.env:
```dotenv
# one of: rr, sync
RR_PARALLEL_DRIVER=rr
RR_PARALLEL_RPC_HOST=0.0.0.0
RR_PARALLEL_RPC_PORT=9044
RR_PARALLEL_KV_STORAGE_NAME=parallel
RR_PARALLEL_WORKERS_NUMBER=5
RR_PARALLEL_WORKERS_MAX_NUMBER=20
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
command= /app./rr serve --dotenv /app/.env -c /app/.rr-parallel.yaml
stdout_logfile=/var/log/supervisor/rr-server-out.log
stderr_logfile=/var/log/supervisor/rr-server-err.log
autostart=true
autorestart=true
startsecs=0

[program:rr-jobs-monitor]
command= php /app/artisan rr-parallel:monitor
stdout_logfile=/var/log/supervisor/rr-jobs-monitor-out.log
stderr_logfile=/var/log/supervisor/rr-jobs-monitor-err.log
autostart=true
autorestart=true
startsecs=0
```
