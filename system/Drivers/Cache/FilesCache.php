<?php

namespace System\Drivers\Cache;

/**
 * Class FilesCache
 * Simple key-value cache using files
 */
class FilesCache extends Cache
{

    protected $cacheDir;

    /**
     * Connect to file storage system
     */
    protected function connect()
    {
        $this->cacheDir = PATH_WRITE . 'cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Save value to cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live (seconds)
     * @return bool
     */
    public function set($key, $value, $ttl = 3600)
    {
        $startTime = microtime(true);
        $cacheFile = $this->cacheDir . md5($key);
        $data = [
            'value' => serialize($value),
            'expiry' => time() + $ttl
        ];
        $result = file_put_contents($cacheFile, json_encode($data)) !== false;

        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('set', $key, $value, $ttl, $result, microtime(true) - $startTime);
        }

        return $result;
    }

    /**
     * Get value from cache
     *
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        $startTime = microtime(true);
        $cacheFile = $this->cacheDir . md5($key);
        $hit = false;
        $value = null;

        if (file_exists($cacheFile)) {
            $data = @json_decode(file_get_contents($cacheFile), true);
            if (isset($data['expiry']) && $data['expiry'] > time()) {
                $value = unserialize($data['value']);
                $hit = true;
            } else {
                $this->delete($key);
            }
        }

        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('get', $key, $value, 0, $hit, microtime(true) - $startTime);
        }

        return $value;
    }

    /**
     * Delete cache by key
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $startTime = microtime(true);
        $cacheFile = $this->cacheDir . md5($key);
        $result = file_exists($cacheFile) ? @unlink($cacheFile) : false;

        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('delete', $key, null, 0, $result, microtime(true) - $startTime);
        }

        return $result;
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $cacheFile = $this->cacheDir . md5($key);
        return file_exists($cacheFile);
    }

    /**
     * Clear all cache
     *
     * @return bool
     */
    public function clear()
    {
        $startTime = microtime(true);
        $files = glob($this->cacheDir . '*');
        $count = 0;
        foreach ($files as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        $result = $count > 0;

        // Track cache operation for debugbar
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $this->trackCacheOperation('clear', 'all', null, 0, $result, microtime(true) - $startTime, $count);
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
    private function trackCacheOperation($operation, $key, $value, $ttl, $success, $executionTime, $count = 1)
    {
        global $debug_cache;

        $debug_cache[] = [
            'operation' => $operation,
            'key' => $key,
            'value_type' => is_object($value) ? get_class($value) : gettype($value),
            'value_size' => is_string($value) ? strlen($value) : (is_array($value) ? count($value) : 0),
            'ttl' => $ttl,
            'success' => $success,
            'execution_time' => round($executionTime * 1000, 2),
            'count' => $count,
            'driver' => 'FilesCache',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }
}
