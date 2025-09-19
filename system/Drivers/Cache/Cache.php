<?php
namespace System\Drivers\Cache;

abstract class Cache {
    
    protected $config;

    /**
     * Initialize Cache
     *
     * @param array $config Cache configuration
     */
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Abstract method to connect to Cache driver (Redis, File,...)
     */
    abstract protected function connect();

    /**
     * Save a value to cache
     *
     * @param string $key Key of the value
     * @param mixed $value Value to save
     * @param int $ttl Time to live (optional)
     * @return bool Returns true if save successful
     */
    abstract public function set($key, $value, $ttl = 3600);

    /**
     * Get value from cache by key
     *
     * @param string $key Key of the value
     * @return mixed Stored value or null if not exists
     */
    abstract public function get($key);

    /**
     * Delete a value from cache
     *
     * @param string $key Key of the value
     * @return bool Returns true if delete successful
     */
    abstract public function delete($key);

    /**
     * Check if a value exists in cache
     *
     * @param string $key Key of the value
     * @return bool Returns true if exists
     */
    abstract public function has($key);

    /**
     * Clear all values in cache
     *
     * @return bool Returns true if clear successful
     */
    abstract public function clear();
}
