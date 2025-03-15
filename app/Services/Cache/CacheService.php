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
     * This constructor sets the default cache driver from the configuration,
     * the default TTL from the configuration, and the key prefix from the configuration.
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
     * If the default TTL is not set in the configuration, it will return 3600 seconds (1 hour).
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
     * This method caches the result of the given callback for a specified duration.
     * If the cache driver supports tags, it will use tags to manage cache keys.
     * Otherwise, it maintains an index of keys associated with a tag.
     *
     * @param  string  $key  The unique cache key.
     * @param  int|null  $ttl  Cache duration in seconds. If null, the default TTL is used.
     * @param  callable  $callback  The closure to execute if the key is not present.
     * @return mixed The result of the callback, either cached or freshly computed.
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        $duration = $ttl ?? $this->defaultTTL;
        $fullKey = $this->keyPrefix.$key;
        $tag = $this->keyPrefix.$this->extractTagFromKey($key);

        if ($this->driverSupportsTags()) {
            // Cache driver supports tags, use them for managing cache keys.
            return Cache::tags([$tag])->remember($fullKey, now()->addSeconds($duration), $callback);
        } else {
            // Update index: store a list of keys related to the tag.
            $indexKey = $tag.'_cache_keys';
            $keys = Cache::get($indexKey, []);
            if (! in_array($fullKey, $keys, true)) {
                $keys[] = $fullKey;
                // Persist the index; optionally, set a TTL for the index separately.
                Cache::forever($indexKey, $keys);
            }

            return Cache::remember($fullKey, now()->addSeconds($duration), $callback);
        }
    }

    /**
     * Remove the specified cache entry.
     *
     * This method appends the key prefix to the given cache key and attempts
     * to remove the corresponding cache entry.
     *
     * @param  string  $key  The cache key to forget.
     */
    public function forget(string $key): void
    {
        // Append the key prefix to the given key.
        $fullKey = $this->keyPrefix.$key;

        // Forget the cache entry associated with the full key.
        Cache::forget($fullKey);
    }

    /**
     * Generate a unique cache key using the identifier and arguments.
     *
     * This method sorts the arguments, encodes them to JSON, and then generates
     * an MD5 hash to ensure a unique and consistent cache key format.
     *
     * @param  string  $identifier  A unique identifier (e.g., repository + method name).
     * @param  array  $args  Arguments to hash into the key.
     * @return string The generated cache key.
     */
    public function generateKey(string $identifier, array $args): string
    {
        // Sort arguments to ensure consistent order for hashing.
        ksort($args);

        // Create a unique cache key by combining the identifier with a hash of the arguments.
        return sprintf('%s_%s', $this->keyPrefix.$identifier, md5(json_encode($args)));
    }

    /**
     * Flush all cache entries associated with the given tag.
     *
     * This method will look for all cache entries associated with the given
     * tag and remove them. If the cache driver supports tags, the method will
     * use the tag to invalidate the cache entries. If the driver does not
     * support tags, the method will use an index key to retrieve a list of
     * cache keys associated with the tag, and then remove each key
     * individually.
     *
     * @param  string  $tag  The tag to flush.
     */
    public function flushByTag(string $tag): void
    {
        $prefixedTag = $this->keyPrefix.$tag;
        if ($this->driverSupportsTags()) {
            // Cache driver supports tags, so use the tag to invalidate the cache entries.
            Cache::tags([$prefixedTag])->flush();
        } else {
            // Cache driver does not support tags, so retrieve the list of cache keys from the index.
            $indexKey = $prefixedTag.'_cache_keys';
            $keys = Cache::get($indexKey, []);
            // Remove each cache key individually.
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            // Remove the index key after removing all the cache entries.
            Cache::forget($indexKey);
        }
    }

    /**
     * Determine if the current cache driver supports tags.
     *
     * This method checks whether the cache driver in use is one of the
     * supported drivers that allow tagging of cache entries.
     *
     * @return bool True if the driver supports tags, false otherwise.
     */
    protected function driverSupportsTags(): bool
    {
        // List of cache drivers that support tagging.
        $supported = ['redis', 'memcached'];

        // Check if the current driver is in the list of supported drivers.
        return in_array($this->cacheDriver, $supported, true);
    }

    /**
     * Extract the tag from a cache key.
     * Example: key is "permissions:all_abc123", tag will be "permissions".
     *
     * This method is used to extract the tag from a cache key. The tag is the
     * first part of the key, separated by a colon (:). If the key does not
     * contain a colon, the whole key is considered to be the tag.
     *
     * @param  string  $key  The cache key.
     * @return string The extracted tag.
     */
    protected function extractTagFromKey(string $key): string
    {
        // Split the key into parts, using the colon (:) as the separator.
        // The first part is the tag.
        $parts = explode(':', $key);

        // Return the first part, or the whole key if it does not contain a colon.
        return $parts[0] ?? $key;
    }
}
