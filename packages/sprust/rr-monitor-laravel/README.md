
# RoadRunner monitor for laravel 

## Installation

```bash
php artisan vendor:publish --tag=rr-monitor-laravel
```

### App

system:
```bash
php artisan vendor:publish --tag=rr-monitor-laravel
```

.env:
```dotenv
RR_MONITOR_RPC_HOST=0.0.0.0
RR_MONITOR_RPC_PORT=9044
```

supervisor example:
```
[program:rr-jobs-monitor]
command= php /app/artisan rr-monitor:start jobs
stdout_logfile=/var/log/supervisor/rr-jobs-monitor-out.log
stderr_logfile=/var/log/supervisor/rr-jobs-monitor-err.log
autostart=true
autorestart=true
startsecs=0
```
