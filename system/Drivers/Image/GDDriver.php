<?php

namespace System\Drivers\Image;

use System\Libraries\Logger;

/**
 * GD-based image driver for image processing in PHP.
 * Supports loading, resizing, cropping, watermarking, format conversion, and saving images using the GD extension.
 * Handles resource management and deep cloning for safe image manipulation.
 */
class GDDriver implements ImageDriver
{
    private $image = null;
    private $width = 0;
    private $height = 0;
    private $type = '';

    /**
     * Load an image from a file.
     * @param string $filename Path to the image file
     * @param array $options Optional driver options
     * @return self
     * @throws \Exception If the file is not a valid image
     */
    public static function load($filename, $options = [])
    {
        if (empty($filename) || !is_string($filename)) {
            throw new \Exception("Invalid filename provided to GDDriver::load");
        }

        $instance = new self();
        $instance->loadImage($filename);
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
        $instance->image = imagecreatefromstring($data);
        if (!$instance->image) {
            throw new \Exception("Cannot create image from Base64 string.");
        }
        $instance->width = imagesx($instance->image);
        $instance->height = imagesy($instance->image);
        $instance->type = 'jpg';
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
        $instance->image = imagecreatefromstring($content);
        if (!$instance->image) {
            throw new \Exception("Cannot create image from content");
        }
        $instance->width = imagesx($instance->image);
        $instance->height = imagesy($instance->image);
        $instance->type = 'jpg';
        return $instance;
    }
    private function loadImage($filename)
    {
        if (empty($filename) || !is_string($filename)) {
            throw new \Exception("Invalid filename provided");
        }

        if (!file_exists($filename)) {
            throw new \Exception("File does not exist: $filename");
        }
        $info = getimagesize($filename);
        if (!$info) {
            throw new \Exception("File is not a valid image: $filename");
        }
        $mime = $info['mime'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed)) {
            throw new \Exception("Image format not allowed: $mime");
        }
        switch ($mime) {
            case 'image/jpeg':
                $this->image = imagecreatefromjpeg($filename);
                $this->type = 'jpg';
                break;
            case 'image/png':
                $this->image = imagecreatefrompng($filename);
                $this->type = 'png';
                break;
            case 'image/gif':
                $this->image = imagecreatefromgif($filename);
                $this->type = 'gif';
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $this->image = imagecreatefromwebp($filename);
                    $this->type = 'webp';
                } else {
                    throw new \Exception("WebP support not available.");
                }
                break;
            default:
                throw new \Exception("Image format not supported: $mime");
        }
        if (!$this->image) {
            throw new \Exception("Cannot load image from file: $filename");
        }
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
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
        if (!$this->image || !(is_resource($this->image) || (class_exists('GdImage', false) && $this->image instanceof \GdImage))) {
            throw new \Exception('Image not loaded');
        }
        if (!$maintainAspect) {
            return $this->_resampleTo($width, $height);
        }
        $ratioW = $width / $this->width;
        $ratioH = $height / $this->height;
        $scale  = $cover ? max($ratioW, $ratioH) : min($ratioW, $ratioH);
        $scaledW = (int) round($this->width  * $scale);
        $scaledH = (int) round($this->height * $scale);
        $scaled = $this->_createBlank($scaledW, $scaledH);
        imagecopyresampled($scaled, $this->image, 0, 0, 0, 0, $scaledW, $scaledH, $this->width, $this->height);
        if (!$cover) {
            imagedestroy($this->image);
            $this->image  = $scaled;
            $this->width  = $scaledW;
            $this->height = $scaledH;
            return $this;
        }
        $cropX = (int) floor(($scaledW - $width) / 2);
        $cropY = (int) floor(($scaledH - $height) / 2);
        $final = $this->_createBlank($width, $height);
        imagecopy($final, $scaled, 0, 0, $cropX, $cropY, $width, $height);
        imagedestroy($this->image);
        imagedestroy($scaled);
        $this->image  = $final;
        $this->width  = $width;
        $this->height = $height;
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
        if (!$this->image) throw new \Exception("Image not loaded");
        $newImg = imagecreatetruecolor($cropWidth, $cropHeight);
        if (in_array($this->type, ['png', 'gif'])) {
            imagecolortransparent($newImg, imagecolorallocatealpha($newImg, 0, 0, 0, 127));
            imagealphablending($newImg, false);
            imagesavealpha($newImg, true);
        }
        imagecopy($newImg, $this->image, 0, 0, $x, $y, $cropWidth, $cropHeight);
        imagedestroy($this->image);
        $this->image = $newImg;
        $this->width = $cropWidth;
        $this->height = $cropHeight;
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
        $targetRatio = $ratioWidth / $ratioHeight;
        $currentRatio = $this->width / $this->height;
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
        if (!$this->image) throw new \Exception("Image not loaded");
        $info = @getimagesize($watermarkFile);
        if (!$info) throw new \Exception("Watermark is not a valid image: $watermarkFile");
        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
                $wm = imagecreatefromjpeg($watermarkFile);
                break;
            case 'image/png':
                $wm = imagecreatefrompng($watermarkFile);
                break;
            case 'image/gif':
                $wm = imagecreatefromgif($watermarkFile);
                break;
            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) throw new \Exception('WebP not supported');
                $wm = imagecreatefromwebp($watermarkFile);
                break;
            default:
                throw new \Exception("Watermark format not supported: $mime");
        }
        
        $wmW = imagesx($wm);
        $wmH = imagesy($wm);
        
        // Calculate optimal watermark size based on image dimensions
        // Watermark should be max 20% of image width or height, whichever is smaller
        $maxWatermarkWidth = (int)($this->width * 0.2);
        $maxWatermarkHeight = (int)($this->height * 0.2);
        
        // Calculate scale ratio to fit watermark within limits
        $scaleX = $maxWatermarkWidth / $wmW;
        $scaleY = $maxWatermarkHeight / $wmH;
        $scale = min($scaleX, $scaleY, 1.0); // Don't scale up, only down
        
        // Calculate new watermark dimensions
        $newWmW = (int)($wmW * $scale);
        $newWmH = (int)($wmH * $scale);
        
        // Create scaled watermark if needed
        if ($scale < 1.0) {
            $scaledWm = imagecreatetruecolor($newWmW, $newWmH);
            imagealphablending($scaledWm, false);
            imagesavealpha($scaledWm, true);
            $transparent = imagecolorallocatealpha($scaledWm, 0, 0, 0, 127);
            imagefill($scaledWm, 0, 0, $transparent);
            imagecopyresampled($scaledWm, $wm, 0, 0, 0, 0, $newWmW, $newWmH, $wmW, $wmH);
            imagedestroy($wm);
            $wm = $scaledWm;
            $wmW = $newWmW;
            $wmH = $newWmH;
        }
        
        // Calculate position
        switch ($position) {
            case 'top-left':
                $destX = $padding;
                $destY = $padding;
                break;
            case 'top-right':
                $destX = $this->width - $wmW - $padding;
                $destY = $padding;
                break;
            case 'bottom-left':
                $destX = $padding;
                $destY = $this->height - $wmH - $padding;
                break;
            case 'center':
                $destX = (int)(($this->width - $wmW) / 2);
                $destY = (int)(($this->height - $wmH) / 2);
                break;
            default:
                $destX = $this->width - $wmW - $padding;
                $destY = $this->height - $wmH - $padding;
        }
        
        // Ensure watermark doesn't go outside image bounds
        $destX = max(0, min($destX, $this->width - $wmW));
        $destY = max(0, min($destY, $this->height - $wmH));
        
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);
        $hasAlpha = in_array($mime, ['image/png', 'image/webp', 'image/gif'], true);
        $opacity = max(0, min(100, $opacity));
        
        if ($opacity >= 100) {
            imagecopy($this->image, $wm, $destX, $destY, 0, 0, $wmW, $wmH);
        } elseif ($hasAlpha) {
            $tmp = imagecreatetruecolor($wmW, $wmH);
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
            imagefill($tmp, 0, 0, $transparent);
            imagecopy($tmp, $wm, 0, 0, 0, 0, $wmW, $wmH);
            $alpha = 127 - (int) round(127 * ($opacity / 100));
            imagefilter($tmp, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha);
            imagecopy($this->image, $tmp, $destX, $destY, 0, 0, $wmW, $wmH);
            imagedestroy($tmp);
        } else {
            imagecopymerge($this->image, $wm, $destX, $destY, 0, 0, $wmW, $wmH, $opacity);
        }
        
        imagedestroy($wm);
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
        if (!in_array($format, ['jpg', 'png', 'gif', 'webp'])) {
            throw new \Exception("Format not supported: $format");
        }

        // Thực sự convert image nếu cần thiết
        if ($this->type !== $format) {
            // Tạo image mới với format mới
            $width = imagesx($this->image);
            $height = imagesy($this->image);
            $newImage = imagecreatetruecolor($width, $height);

            // Copy image cũ sang image mới
            imagecopy($newImage, $this->image, 0, 0, 0, 0, $width, $height);

            // Destroy image cũ
            imagedestroy($this->image);

            // Set image mới
            $this->image = $newImage;
            $this->type = $format;
        }

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
        // Kiểm tra image resource
        if (!$this->image || (!is_resource($this->image) && !(class_exists('GdImage', false) && $this->image instanceof \GdImage))) {
            throw new \Exception("Invalid image resource. Image may have been destroyed.");
        }

        switch ($this->type) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($this->image, $destination, $quality);
            case 'png':
                $pngQuality = round((100 - $quality) / 10);
                return imagepng($this->image, $destination, $pngQuality);
            case 'gif':
                return imagegif($this->image, $destination);
            case 'webp':
                if (function_exists('imagewebp')) {
                    return imagewebp($this->image, $destination, $quality);
                } else {
                    throw new \Exception("WebP support not available.");
                }
            default:
                throw new \Exception("Save format not supported: {$this->type}");
        }
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
        // Kiểm tra image resource
        if (!$this->image || (!is_resource($this->image) && !(class_exists('GdImage', false) && $this->image instanceof \GdImage))) {
            throw new \Exception("Invalid image resource. Image may have been destroyed.");
        }

        ob_start();
        $fmt = $format ? strtolower($format) : $this->type;
        switch ($fmt) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->image, null, $quality);
                break;
            case 'png':
                $pngQuality = round((100 - $quality) / 10);
                imagepng($this->image, null, $pngQuality);
                break;
            case 'gif':
                imagegif($this->image);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($this->image, null, $quality);
                } else {
                    throw new \Exception("WebP support not available.");
                }
                break;
            default:
                throw new \Exception("Output format not supported: $fmt");
        }
        $data = ob_get_contents();
        ob_end_clean();
        return 'data:image/' . $fmt . ';base64,' . base64_encode($data);
    }
    /**
     * Destroy and clean up the image resource.
     * @return void
     */
    public function destroy()
    {
        if ($this->image) {
            imagedestroy($this->image);
            $this->image = null;
        }
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
    /**
     * Deep clone GDDriver, ensure image resource is copied.
     * @return void
     */
    public function __clone()
    {
        if ($this->image && (is_resource($this->image) || (class_exists('GdImage', false) && $this->image instanceof \GdImage))) {
            try {
                $width = imagesx($this->image);
                $height = imagesy($this->image);
                $newImage = imagecreatetruecolor($width, $height);

                // Copy image với alpha channel support
                if (in_array($this->type, ['png', 'gif'], true)) {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
                    imagefill($newImage, 0, 0, $transparent);
                }

                imagecopy($newImage, $this->image, 0, 0, 0, 0, $width, $height);
                $this->image = $newImage;
                $this->width = $width;
                $this->height = $height;
            } catch (\Exception $e) {
                // Nếu clone thất bại, set image về null
                $this->image = null;
                throw new \Exception("Failed to clone image: " . $e->getMessage());
            }
        }
    }
    private function _createBlank($w, $h)
    {
        $img = imagecreatetruecolor($w, $h);
        if (in_array($this->type, ['png', 'gif'], true)) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparent);
        }
        return $img;
    }
    private function _resampleTo($w, $h)
    {
        $dst = $this->_createBlank($w, $h);
        imagecopyresampled($dst, $this->image, 0, 0, 0, 0, $w, $h, $this->width, $this->height);
        imagedestroy($this->image);
        $this->image  = $dst;
        $this->width  = $w;
        $this->height = $h;
        return $this;
    }
}
