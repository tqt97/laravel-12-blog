<?php

namespace App\Services\Cache;

interface CacheServiceInterface
{
    /**
     * Cache the result of a closure.
     *
     * If $ttl is null, the default TTL from configuration will be used.
     *
     * @param  string  $key  The unique cache key.
     * @param  int|null  $ttl  Cache duration in seconds.
     * @param  callable  $callback  The closure to execute if the key is not present.
     * @return mixed
     */
    public function remember(string $key, ?int $ttl, callable $callback);

    /**
     * Remove the given cache key.
     *
     * @param  string  $key  The cache key to forget.
     */
    public function forget(string $key): void;

    /**
     * Generate a unique cache key based on an identifier and arguments.
     *
     * @param  string  $identifier  A unique identifier (e.g., repository + method name).
     * @param  array  $args  Arguments to hash into the key.
     */
    public function generateKey(string $identifier, array $args): string;

    /**
     * Flush all cache entries associated with the given tag.
     *
     * @param  string  $tag  The tag to flush.
     */
    public function flushByTag(string $tag): void;

    /**
     * Get the default TTL in seconds.
     */
    public function getDefaultTTL(): int;
}
