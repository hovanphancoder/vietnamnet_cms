<?php
namespace System\Drivers\Cache;

/**
 * Class FilesCache
 * Simple key-value cache using files
 */
class FilesCache extends Cache {

    protected $cacheDir;

    /**
     * Connect to file storage system
     */
    protected function connect() {
        $this->cacheDir = PATH_ROOT . '/writeable/Cache/';
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
    public function set($key, $value, $ttl = 3600) {
        $cacheFile = $this->cacheDir . md5($key);
        $data = [
            'value' => serialize($value),
            'expiry' => time() + $ttl
        ];
        return file_put_contents($cacheFile, json_encode($data)) !== false;
    }

    /**
     * Get value from cache
     *
     * @param string $key
     * @return mixed|null
     */
    public function get($key) {
        $cacheFile = $this->cacheDir . md5($key);
        if (file_exists($cacheFile)) {
            $data = @json_decode(file_get_contents($cacheFile), true);
            if (isset($data['expiry']) && $data['expiry'] > time()) {
                return unserialize($data['value']);
            }
            $this->delete($key);
        }
        return null;
    }

    /**
     * Delete cache by key
     *
     * @param string $key
     * @return bool
     */
    public function delete($key) {
        $cacheFile = $this->cacheDir . md5($key);
        return file_exists($cacheFile) ? @unlink($cacheFile) : false;
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key
     * @return bool
     */
    public function has($key) {
        $cacheFile = $this->cacheDir . md5($key);
        return file_exists($cacheFile);
    }

    /**
     * Clear all cache
     *
     * @return bool
     */
    public function clear() {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
}
