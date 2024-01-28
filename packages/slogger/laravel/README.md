
# SLogger for laravel 

## Installation

### App

system:
```bash
php artisan vendor:publish --tag=slogger-laravel
```

.env:
```dotenv
SLOGGER_LOG_REQUESTS_ENABLED=true
SLOGGER_LOG_COMMANDS_ENABLED=true
SLOGGER_LOG_DATABASE_ENABLED=true
SLOGGER_LOG_LOG_ENABLED=true
SLOGGER_LOG_SCHEDULE_ENABLED=true
SLOGGER_LOG_JOBS_ENABLED=true
```
