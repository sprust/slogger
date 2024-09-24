
# RoadRunner concurrency for laravel 

## Installation

### App

system:
```bash
./vendor/bin/rr get-binary
php artisan vendor:publish --tag=rr-concurrency-laravel
```

.env:
```dotenv
RR_CONCURRENCY_RPC_HOST=0.0.0.0
RR_CONCURRENCY_RPC_PORT=9044
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
```
