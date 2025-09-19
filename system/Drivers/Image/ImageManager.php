<?php

namespace System\Drivers\Image;

use System\Libraries\Logger;

/**
 * ImageManager acts as a facade and factory for image processing, automatically selecting the appropriate driver (GD or Binary) based on server capabilities or options.
 * Provides a unified interface for image manipulation, supporting method chaining and resource management.
 */
class ImageManager implements ImageDriver
{
    /** @var ImageDriver */
    private $driver;

    /**
     * Construct an ImageManager and select the appropriate driver.
     * If a driver is injected, it will be used directly. Otherwise, selection is based on options and server capabilities.
     *
     * @param array $options Driver options (e.g., ['driver' => 'gd'|'binary', 'use_binary' => true|false])
     * @param ImageDriver|null $driver Optional driver instance to inject
     */
    public function __construct($options = [], $driver = null)
    {
        if ($driver instanceof ImageDriver) {
            $this->driver = $driver;
            return;
        }
        
        // Ưu tiên GD driver trước, chỉ sử dụng BinaryDriver nếu được yêu cầu cụ thể
        $forceDriver = isset($options['driver']) ? strtolower($options['driver']) : null;
        $useBinary = isset($options['use_binary']) ? (bool)$options['use_binary'] : null;
        
        if ($forceDriver === 'binary' || $useBinary === true) {
            // Chỉ sử dụng BinaryDriver nếu được yêu cầu cụ thể
            if (function_exists('exec')) {
                $disabled = explode(',', ini_get('disable_functions'));
                if (!in_array('exec', $disabled)) {
                    $this->driver = new BinaryDriver($options);
                    return;
                }
            }
            // Nếu không thể sử dụng BinaryDriver, fallback về GD
            $this->driver = new GDDriver($options);
        } else {
            // Mặc định sử dụng GD
            $this->driver = new GDDriver($options);
        }
    }

    /**
     * Load image from file, reusing driver if possible.
     *
     * @param string $filename Path to the image file
     * @param array $options Optional driver options
     * @return self
     */
    public static function load($filename, $options = [])
    {
        $manager = new self($options);
        if ($manager->driver) {
            $manager->driver = $manager->driver->load($filename, $options);
        } else {
            $manager->driver = (new GDDriver($options))->load($filename, $options);
        }
        return $manager;
    }
    /**
     * Load image from base64 string, reusing driver if possible.
     *
     * @param string $base64String Base64-encoded image data
     * @param array $options Optional driver options
     * @return self
     */
    public static function loadFromBase64($base64String, $options = [])
    {
        $manager = new self($options);
        if ($manager->driver) {
            $manager->driver = $manager->driver->loadFromBase64($base64String, $options);
        } else {
            $manager->driver = (new GDDriver($options))->loadFromBase64($base64String, $options);
        }
        return $manager;
    }
    /**
     * Load image from raw content, reusing driver if possible.
     *
     * @param string $content Raw image data
     * @param array $options Optional driver options
     * @return self
     */
    public static function loadFromContent($content, $options = [])
    {
        $manager = new self($options);
        if ($manager->driver) {
            $manager->driver = $manager->driver->loadFromContent($content, $options);
        } else {
            $manager->driver = (new GDDriver($options))->loadFromContent($content, $options);
        }
        return $manager;
    }
    /**
     * Resize the image.
     * @param int $width Target width
     * @param int $height Target height
     * @param bool $maintainAspect Maintain aspect ratio
     * @param bool $cover If true, crop to cover target size
     * @return $this
     */
    public function resize($width, $height, $maintainAspect = true, $cover = false)
    {
        $this->driver->resize($width, $height, $maintainAspect, $cover);
        return $this;
    }
    /**
     * Crop the image to the specified rectangle.
     * @param int $x X offset
     * @param int $y Y offset
     * @param int $cropWidth Width of crop
     * @param int $cropHeight Height of crop
     * @return $this
     */
    public function crop($x, $y, $cropWidth, $cropHeight)
    {
        $this->driver->crop($x, $y, $cropWidth, $cropHeight);
        return $this;
    }
    /**
     * Crop the image to a specific aspect ratio, centered.
     * @param int $ratioWidth Target aspect width
     * @param int $ratioHeight Target aspect height
     * @return $this
     */
    public function cropByRatio($ratioWidth, $ratioHeight)
    {
        $this->driver->cropByRatio($ratioWidth, $ratioHeight);
        return $this;
    }
    /**
     * Add a watermark image to the current image.
     * @param string $watermarkFile Path to watermark image
     * @param string $position Position (e.g. 'bottom-right')
     * @param int $padding Padding in pixels
     * @param int $opacity Opacity (0-100)
     * @return $this
     */
    public function addWatermark($watermarkFile, $position = 'bottom-right', $padding = 10, $opacity = 100)
    {
        $this->driver->addWatermark($watermarkFile, $position, $padding, $opacity);
        return $this;
    }
    /**
     * Convert the image to a different format.
     * @param string $format Target format (jpg, png, gif, webp)
     * @param int $quality Quality (default 90)
     * @return $this
     */
    public function convert($format, $quality = 90)
    {
        $this->driver->convert($format, $quality);
        return $this;
    }
    /**
     * Save the image to a file.
     * @param string $destination Destination file path
     * @param int $quality Quality (default 90)
     * @return $this
     */
    public function save($destination, $quality = 90)
    {
        $this->driver->save($destination, $quality);
        return $this;
    }
    /**
     * Output the image as a base64-encoded data URI.
     * @param string|null $format Output format (optional)
     * @param int $quality Quality (default 90)
     * @return string Data URI
     */
    public function output($format = null, $quality = 90)
    {
        return $this->driver->output($format, $quality);
    }
    /**
     * Destroy and clean up the image resource.
     * @return void
     */
    public function destroy()
    {
        $this->driver->destroy();
    }
    /**
     * Get the width of the image in pixels.
     * @return int
     */
    public function getWidth()
    {
        return $this->driver->getWidth();
    }
    /**
     * Get the height of the image in pixels.
     * @return int
     */
    public function getHeight()
    {
        return $this->driver->getHeight();
    }
    /**
     * Get the current image type/format (jpg, png, gif, webp).
     * @return string
     */
    public function getType()
    {
        return $this->driver->getType();
    }
    /**
     * Get the class name of the current driver.
     * @return string
     */
    public function getDriverName()
    {
        return get_class($this->driver);
    }
    /**
     * Deep clone the manager and its underlying driver.
     * @return void
     */
    public function __clone()
    {
        if ($this->driver && is_object($this->driver)) {
            $this->driver = clone $this->driver;
        }
    }
}
