<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheService implements CacheServiceInterface
{
    protected int $defaultTTL;

    protected string $keyPrefix;

    protected string $cacheDriver;

    /**
     * CacheService constructor.
     *
     * Initializes the cache driver, default TTL, and key prefix from configuration.
     */
    public function __construct()
    {
        $this->cacheDriver = config('cache.default');
        $this->defaultTTL = config('cache.default_ttl', 3600);
        $this->keyPrefix = config('cache.prefix', '');
    }

    /**
     * Get the default TTL in seconds.
     *
     * @return int The default TTL in seconds.
     */
    public function getDefaultTTL(): int
    {
        return $this->defaultTTL;
    }

    /**
     * Cache the result of a closure.
     *
     * @param  string  $key  The unique cache key.
     * @param  int|null  $ttl  Cache duration in seconds. Defaults to the default TTL if null.
     * @param  callable  $callback  The closure to execute if the key is not present.
     * @return mixed The result of the callback, either cached or freshly computed.
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $duration = $ttl ?? $this->defaultTTL;
        $fullKey = $this->getFullCacheKey($key);
        $tag = $this->keyPrefix.$this->extractTagFromKey($key);

        if ($this->driverSupportsTags()) {
            return Cache::tags([$tag])->remember($fullKey, now()->addSeconds($duration), $callback);
        } else {
            $indexKey = $tag.'_cache_keys';
            $keys = Cache::get($indexKey, []);
            if (! in_array($fullKey, $keys, true)) {
                $keys[] = $fullKey;
                Cache::forever($indexKey, $keys);
            }

            return Cache::remember($fullKey, now()->addSeconds($duration), $callback);
        }
    }

    /**
     * Remove the specified cache entry.
     *
     * @param  string  $key  The cache key to forget.
     */
    public function forget(string $key): void
    {
        $fullKey = $this->getFullCacheKey($key);
        Cache::forget($fullKey);
    }

    /**
     * Generate a unique cache key using the identifier and arguments.
     *
     * @param  string  $identifier  A unique identifier (e.g., repository + method name).
     * @param  array  $args  Arguments to hash into the key.
     * @return string The generated cache key.
     */
    public function generateKey(string $identifier, array $args): string
    {
        ksort($args);

        return sprintf('%s_%s', $this->keyPrefix.$identifier, md5(json_encode($args)));
    }

    /**
     * Flush all cache entries associated with the given tag.
     *
     * @param  string  $tag  The tag to flush.
     */
    public function flushByTag(string $tag): void
    {
        $prefixedTag = $this->keyPrefix.$tag;
        if ($this->driverSupportsTags()) {
            Cache::tags([$prefixedTag])->flush();
        } else {
            $indexKey = $prefixedTag.'_cache_keys';
            $keys = Cache::get($indexKey, []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            Cache::forget($indexKey);
        }
    }

    /**
     * Determine if the current cache driver supports tags.
     *
     * @return bool True if the driver supports tags, false otherwise.
     */
    protected function driverSupportsTags(): bool
    {
        // Retrieve the list of cache drivers that support tagging from configuration.
        $supported = config('cache.supported_tags_drivers', ['redis', 'memcached']);

        return in_array($this->cacheDriver, $supported, true);
    }

    /**
     * Extract the tag from a cache key.
     *
     * @param  string  $key  The cache key.
     * @return string The extracted tag.
     */
    protected function extractTagFromKey(string $key): string
    {
        $parts = explode(':', $key);

        return $parts[0] ?? $key;
    }

    /**
     * Generate the full cache key with prefix.
     *
     * @param  string  $key  The original cache key.
     * @return string The full cache key with prefix.
     */
    private function getFullCacheKey(string $key): string
    {
        return $this->keyPrefix.$key;
    }
}
