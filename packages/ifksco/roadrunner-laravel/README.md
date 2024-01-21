
# RoadRunner for laravel 

## Installation

### App

system:
```bash
./vendor/bin/rr get-binary
cp -i packages/ifksco/roadrunner-laravel/config/.rr.yaml.example .rr.yaml
php artisan vendor:publish --tag=ifksco-roadrunner-laravel
```

.env:
```dotenv
RR_RPC_HOST=0.0.0.0
RR_RPC_PORT=9010
RR_HTTP_HOST=0.0.0.0
RR_HTTP_PORT=9020
RR_HTTP_DOCKER_PORT=9021
RR_STATUS_HOST=0.0.0.0
RR_STATUS_PORT=2112
RR_STATUS_DOCKER_PORT=22112
RR_METRICS_HOST=0.0.0.0
RR_METRICS_PORT=2114
RR_METRICS_DOCKER_PORT=22114
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
command= /var/www/html/./rr serve -p --dotenv /var/www/html/.env -c /var/www/html/.rr.yaml
stdout_logfile=/var/log/supervisor/rr-server-out.log
stderr_logfile=/var/log/supervisor/rr-server-err.log
autostart=true
autorestart=true
startsecs=0
```
