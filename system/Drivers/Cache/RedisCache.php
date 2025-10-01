<?php

namespace System\Drivers\Cache;

class RedisCache extends Cache
{

    protected $redis;

    /**
     * Connect to Redis
     */
    protected function connect()
    {
        $this->redis = new \Redis();
        $this->redis->connect($this->config['cache_host'], $this->config['cache_port']);
        if (isset($this->config['cache_password'])) {
            $this->redis->auth($this->config['cache_password']);
        }
        $this->redis->select($this->config['cache_database']);
    }

    public function set($key, $value, $ttl = 3600)
    {
        $result = $this->redis->setex($key, $ttl, serialize($value));
        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('set', $key, $value, $ttl, $result);
        }
        return $result;
    }

    public function get($key)
    {
        $result = $this->redis->get($key);
        $result = $result ? unserialize($result) : null;

        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('get', $key, $result, 0, (bool)$result);
        }
        return $result;
    }

    public function delete($key)
    {
        $result = $this->redis->del($key) > 0;
        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('delete', $key, null, 0, $result);
        }
        return $result;
    }

    public function has($key)
    {
        return $this->redis->exists($key);
    }

    public function clear()
    {
        $result = $this->redis->flushDB();

        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('clear', 'all', null, 0, (bool)$result);
        }

        return $result;
    }

    /**
     * Track cache operation for debugbar
     *
     * @param string $operation
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @param bool $success
     * @param float $executionTime
     * @param int $count
     */
    private function trackCacheOperation($operation, $key, $value, $ttl, $success)
    {
        if (isset($GLOBALS['debug_cache']) && is_array($GLOBALS['debug_cache'])) {
            $GLOBALS['debug_cache'][] = [
                'operation' => $operation,
                'key' => $key,
                'value_type' => is_object($value) ? get_class($value) : gettype($value),
                'value_size' => is_string($value) ? strlen($value) : (is_array($value) ? count($value) : 0),
                'ttl' => $ttl,
                'success' => $success,
                'execution_time' => 1,
                'count' => 1,
                'driver' => 'RedisCache',
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }
    }
}
