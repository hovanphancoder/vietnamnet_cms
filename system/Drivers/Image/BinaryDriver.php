<?php

namespace System\Drivers\Image;

/**
 * Image driver using external binaries (ImageMagick, cwebp) for image processing.
 * Supports loading, resizing, cropping, watermarking, format conversion, and saving images via command-line tools.
 * Handles temporary files and cleans up resources on destroy.
 */
class BinaryDriver implements ImageDriver
{
    private $file;
    private $width = 0;
    private $height = 0;
    private $type = '';
    private $tmpFiles = [];

    /**
     * Load an image from a file.
     * @param string $filename Path to the image file
     * @param array $options Optional driver options
     * @return self
     * @throws \Exception If the file is not a valid image
     */
    public static function load($filename, $options = [])
    {
        $instance = new self();
        $instance->file = $filename;
        $instance->_updateInfo();
        return $instance;
    }
    /**
     * Load an image from a base64-encoded string.
     * @param string $base64String Base64-encoded image data
     * @param array $options Optional driver options
     * @return self
     * @throws \Exception If decoding fails or image is invalid
     */
    public static function loadFromBase64($base64String, $options = [])
    {
        $instance = new self();
        $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $base64String);
        $data = base64_decode($base64String);
        if ($data === false) {
            throw new \Exception("Base64 decode failed.");
        }
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($tmp, $data);
        $instance->file = $tmp;
        $instance->tmpFiles[] = $tmp;
        $instance->_updateInfo();
        return $instance;
    }
    /**
     * Load an image from raw binary content.
     * @param string $content Raw image data
     * @param array $options Optional driver options
     * @return self
     * @throws \Exception If image is invalid
     */
    public static function loadFromContent($content, $options = [])
    {
        $instance = new self();
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        file_put_contents($tmp, $content);
        $instance->file = $tmp;
        $instance->tmpFiles[] = $tmp;
        $instance->_updateInfo();
        return $instance;
    }
    private function _updateInfo()
    {
        $info = @getimagesize($this->file);
        if (!$info) throw new \Exception("File is not a valid image: {$this->file}");
        $this->width = $info[0];
        $this->height = $info[1];
        $mime = $info['mime'];
        $this->type = $this->_mimeToType($mime);
    }
    private function _mimeToType($mime)
    {
        switch ($mime) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            default:
                return 'jpg';
        }
    }
    private function _run($cmd)
    {
        exec($cmd . ' 2>&1', $out, $code);
        if ($code !== 0) {
            throw new \Exception("ImageMagick error: " . implode("\n", $out));
        }
    }
    /**
     * Resize the image to the given dimensions.
     * @param int $width Target width
     * @param int $height Target height
     * @param bool $maintainAspect Maintain aspect ratio
     * @param bool $cover If true, crop to cover target size
     * @return $this
     * @throws \Exception On failure
     */
    public function resize($width, $height, $maintainAspect = true, $cover = false)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $geometry = $maintainAspect ? ($cover ? "{$width}x{$height}^" : "{$width}x{$height}") : "{$width}x{$height}!";
        $extra = $cover ? " -gravity center -extent {$width}x{$height}" : '';
        $cmd = "convert " . escapeshellarg($this->file) . " -resize {$geometry}{$extra} " . escapeshellarg($tmp);
        $this->_run($cmd);
        $this->file = $tmp;
        $this->tmpFiles[] = $tmp;
        $this->_updateInfo();
        return $this;
    }
    /**
     * Crop the image to the specified rectangle.
     * @param int $x X offset
     * @param int $y Y offset
     * @param int $cropWidth Width of crop
     * @param int $cropHeight Height of crop
     * @return $this
     * @throws \Exception On failure
     */
    public function crop($x, $y, $cropWidth, $cropHeight)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $cmd = "convert " . escapeshellarg($this->file) . " -crop {$cropWidth}x{$cropHeight}+{$x}+{$y} +repage " . escapeshellarg($tmp);
        $this->_run($cmd);
        $this->file = $tmp;
        $this->tmpFiles[] = $tmp;
        $this->_updateInfo();
        return $this;
    }
    /**
     * Crop the image to a specific aspect ratio, centered.
     * @param int $ratioWidth Target aspect width
     * @param int $ratioHeight Target aspect height
     * @return $this
     * @throws \Exception On failure
     */
    public function cropByRatio($ratioWidth, $ratioHeight)
    {
        $currentRatio = $this->width / $this->height;
        $targetRatio = $ratioWidth / $ratioHeight;
        if ($currentRatio > $targetRatio) {
            $newWidth = (int) round($this->height * $targetRatio);
            $x = (int) round(($this->width - $newWidth) / 2);
            return $this->crop($x, 0, $newWidth, $this->height);
        } else {
            $newHeight = (int) round($this->width / $targetRatio);
            $y = (int) round(($this->height - $newHeight) / 2);
            return $this->crop(0, $y, $this->width, $newHeight);
        }
    }
    /**
     * Add a watermark image to the current image.
     * @param string $watermarkFile Path to watermark image
     * @param string $position Position (e.g. 'bottom-right')
     * @param int $padding Padding in pixels
     * @param int $opacity Opacity (0-100)
     * @return $this
     * @throws \Exception On failure
     */
    public function addWatermark($watermarkFile, $position = 'bottom-right', $padding = 10, $opacity = 100)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $gravity = [
            'top-left' => 'NorthWest',
            'top-right' => 'NorthEast',
            'bottom-left' => 'SouthWest',
            'center' => 'Center',
            'bottom-right' => 'SouthEast',
        ];
        $g = $gravity[$position] ?? 'SouthEast';
        $dissolve = $opacity < 100 ? "-dissolve {$opacity}" : '';
        $cmd = "composite -gravity {$g} -geometry +{$padding}+{$padding} {$dissolve} " . escapeshellarg($watermarkFile) . " " . escapeshellarg($this->file) . " " . escapeshellarg($tmp);
        $this->_run($cmd);
        $this->file = $tmp;
        $this->tmpFiles[] = $tmp;
        $this->_updateInfo();
        return $this;
    }
    /**
     * Convert the image to a different format.
     * @param string $format Target format (jpg, png, gif, webp)
     * @param int $quality Quality (default 90)
     * @return $this
     * @throws \Exception On failure
     */
    public function convert($format, $quality = 90)
    {
        $format = strtolower($format);
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $q = ($format === 'jpg' || $format === 'jpeg') ? "-quality {$quality}" : '';
        $cmd = "convert " . escapeshellarg($this->file) . " {$q} " . escapeshellarg($tmp . ".{$format}");
        $this->_run($cmd);
        $tmpFile = $tmp . ".{$format}";
        $this->file = $tmpFile;
        $this->tmpFiles[] = $tmpFile;
        $this->type = $format;
        $this->_updateInfo();
        return $this;
    }
    /**
     * Save the image to a file.
     * @param string $destination Destination file path
     * @param int $quality Quality (default 90)
     * @return bool True on success
     * @throws \Exception On failure
     */
    public function save($destination, $quality = 90)
    {
        if ($this->type === 'webp' && $this->_hasCwebp()) {
            $cmd = "cwebp -q {$quality} " . escapeshellarg($this->file) . " -o " . escapeshellarg($destination);
            $this->_run($cmd);
            return true;
        }
        copy($this->file, $destination);
        return true;
    }
    /**
     * Output the image as a base64-encoded data URI.
     * @param string|null $format Output format (optional)
     * @param int $quality Quality (default 90)
     * @return string Data URI
     * @throws \Exception On failure
     */
    public function output($format = null, $quality = 90)
    {
        $fmt = $format ? strtolower($format) : $this->type;
        $tmp = tempnam(sys_get_temp_dir(), 'img_');
        $outFile = $tmp . ".{$fmt}";
        $this->convert($fmt, $quality)->save($outFile, $quality);
        $data = file_get_contents($outFile);
        $this->tmpFiles[] = $outFile;
        return 'data:image/' . $fmt . ';base64,' . base64_encode($data);
    }
    /**
     * Destroy and clean up all temporary files used by this instance.
     * @return void
     */
    public function destroy()
    {
        foreach ($this->tmpFiles as $f) {
            if (file_exists($f)) @unlink($f);
        }
        $this->tmpFiles = [];
    }
    /**
     * Get the width of the image in pixels.
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }
    /**
     * Get the height of the image in pixels.
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }
    /**
     * Get the current image type/format (jpg, png, gif, webp).
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    private function _hasCwebp()
    {
        exec('which cwebp', $out, $code);
        return $code === 0;
    }
}
