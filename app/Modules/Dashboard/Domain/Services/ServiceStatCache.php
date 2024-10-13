<?php

namespace App\Modules\Dashboard\Domain\Services;

use App\Modules\Dashboard\Entities\ServiceStatObject;
use Illuminate\Support\Facades\Cache;
use Throwable;

readonly class ServiceStatCache
{
    private string $cacheKey;

    public function __construct()
    {
        $this->cacheKey = 'dashboard-service-stat';
    }

    public function flush(): void
    {
        Cache::delete($this->cacheKey);
    }

    public function has(): bool
    {
        return Cache::has($this->cacheKey);
    }

    /**
     * @param ServiceStatObject[] $stats
     */
    public function put(array $stats): void
    {
        Cache::put($this->cacheKey, serialize($stats), now()->addMinutes(2));
    }

    /**
     * @return ServiceStatObject[]|null
     */
    public function get(): ?array
    {
        try {
            return unserialize(Cache::get($this->cacheKey));
        } catch (Throwable) {
            return null;
        }
    }
}
