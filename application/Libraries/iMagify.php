<?php

namespace App\Libraries;

/**
 * iMagify Library
 *
 * Supports multi-tasking image processing:
 *  - Add watermark (with optional position and padding)
 *  - Crop by specific size or ratio (center crop)
 *  - Resize image (respect aspect ratio)
 *  - Convert format and change quality
 *  - Save image from base64 string to file
 *
 * Security checks:
 *  - Check input file via getimagesize to ensure valid image
 *  - Only allow safe image formats: jpg/jpeg, png, gif, webp
 *  - Process base64 string by removing unnecessary headers
 *
 * Requirements: PHP with GD Library (with webp support if available)
 */
class iMagify
{
    private $image;   // GD image resource
    private $width;
    private $height;
    private $type;    // jpg, png, gif, webp

    /* ------------------------------
       Static instance creation methods
       ------------------------------ */

    // Load image from file (with valid MIME check)
    public static function load($filename)
    {
        $instance = new self();
        $instance->loadImage($filename);
        return $instance;
    }

    // Load image from Base64 string
    public static function loadFromBase64($base64String)
    {
        $instance = new self();
        // Remove header if exists (e.g.: data:image/png;base64,)
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
        // If cannot determine type, set default as jpg
        $instance->type = 'jpg';
        return $instance;
    }

    /* ------------------------------
       Internal image loading from file
       ------------------------------ */
    private function loadImage($filename)
    {
        if (!file_exists($filename)) {
            throw new \Exception("File does not exist: $filename");
        }
        // Use getimagesize for safe check
        $info = getimagesize($filename);
        if (!$info) {
            throw new \Exception("File is not a valid image: $filename");
        }
        $mime = $info['mime'];
        // Only allow safe mime types
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

    /* ------------------------------
       Watermark processing
       ------------------------------ */
    /**
     * Add watermark to $this->image (GD resource) and return the object
     *
     * @param string $watermarkFile   Watermark file path
     * @param string $position        top-left | top-right | bottom-left | bottom-right | center
     * @param int    $padding         Edge distance (px)
     * @param int    $opacity         0-100 (100 = original display)
     * @return static
     * @throws \Exception
     */
    public function addWatermark(
        string $watermarkFile,
        string $position = 'bottom-right',
        int    $padding  = 10,
        int    $opacity  = 100
    ) {
        /* ---------- 1. Load watermark safely ---------- */
        $info = @getimagesize($watermarkFile);
        if (!$info) {
            throw new \Exception("Watermark is not a valid image: $watermarkFile");
        }
        $mime     = $info['mime'];
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            throw new \Exception("Watermark format not supported: $mime");
        }

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
                if (!function_exists('imagecreatefromwebp')) {
                    throw new \Exception('Server has not compiled WebP for GD');
                }
                $wm = imagecreatefromwebp($watermarkFile);
                break;
        }
        if (!$wm) {
            throw new \Exception("Cannot load watermark: $watermarkFile");
        }

        /* ---------- 2. Calculate position ---------- */
        $wmW = imagesx($wm);
        $wmH = imagesy($wm);

        switch ($position) {
            case 'top-left':
                $destX = $padding;
                $destY = $padding;
                break;
            case 'top-right':
                $destX = $this->width  - $wmW - $padding;
                $destY = $padding;
                break;
            case 'bottom-left':
                $destX = $padding;
                $destY = $this->height - $wmH - $padding;
                break;
            case 'center':
                $destX = (int) round(($this->width  - $wmW) / 2);
                $destY = (int) round(($this->height - $wmH) / 2);
                break;
            default: // bottom-right
                $destX = $this->width  - $wmW - $padding;
                $destY = $this->height - $wmH - $padding;
        }

        /* ---------- 3. Enable alpha for original image ---------- */
        imagealphablending($this->image, true);
        imagesavealpha($this->image, true);

        /* ---------- 4. Blend watermark ---------- */
        $hasAlpha   = in_array($mime, ['image/png', 'image/webp', 'image/gif'], true);
        $opacity    = max(0, min(100, $opacity));            // clamp 0-100

        if ($opacity >= 100) {
            // Keep watermark as is
            imagecopy($this->image, $wm, $destX, $destY, 0, 0, $wmW, $wmH);
        } elseif ($hasAlpha) {
            /* ---- 4A. PNG/WebP/GIF: reduce transparency *correctly* ---- */
            // Create temporary transparent layer
            $tmp = imagecreatetruecolor($wmW, $wmH);
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);

            // Fill background with transparent color
            $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
            imagefill($tmp, 0, 0, $transparent);

            // Copy original watermark
            imagecopy($tmp, $wm, 0, 0, 0, 0, $wmW, $wmH);

            // Calculate new alpha level (0 = transparent, 127 = opaque)
            $alpha = 127 - (int) round(127 * ($opacity / 100));

            // Reduce transparency evenly
            imagefilter($tmp, IMG_FILTER_COLORIZE, 0, 0, 0, $alpha);

            // Paste onto main image
            imagecopy($this->image, $tmp, $destX, $destY, 0, 0, $wmW, $wmH);
            imagedestroy($tmp);
        } else {
            /* ---- 4B. JPEG: no alpha, use imagecopymerge ---- */
            imagecopymerge($this->image, $wm, $destX, $destY, 0, 0, $wmW, $wmH, $opacity);
        }

        /* ---------- 5. Clean up ---------- */
        imagedestroy($wm);
        return $this;
    }


    /* ------------------------------
       Crop image
       ------------------------------ */
    public function crop($x, $y, $cropWidth, $cropHeight)
    {
        // Ensure crop values are integers
        $x = (int) round($x);
        $y = (int) round($y);
        $cropWidth = (int) round($cropWidth);
        $cropHeight = (int) round($cropHeight);

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

    // Crop image by ratio (center crop)
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

    /* =========================================================
    Resize image
    ---------------------------------------------------------
    $dstW, $dstH        : target size
    $maintainAspect=true: keep aspect ratio (contain / cover)
    $cover=false        : false = contain (vừa khít hộp)
                            true  = cover  (phủ kín rồi crop)
    =======================================================*/
    public function resize(
        int  $dstW,
        int  $dstH,
        bool $maintainAspect = true,
        bool $cover          = false
    ) {
        /* 1. Don't keep aspect ratio  → scale straight */
        if (!$maintainAspect) {
            return $this->_resampleTo($dstW, $dstH);
        }

        /* 2. Keep aspect ratio: calculate scale factor */
        $ratioW = $dstW / $this->width;
        $ratioH = $dstH / $this->height;
        $scale  = $cover ? max($ratioW, $ratioH)    // cover  : fill completely
            : min($ratioW, $ratioH);   // contain: fit exactly

        $scaledW = (int) round($this->width  * $scale);
        $scaledH = (int) round($this->height * $scale);

        /* Step I – scale */
        $scaled = $this->_createBlank($scaledW, $scaledH);
        imagecopyresampled(
            $scaled,
            $this->image,
            0,
            0,
            0,
            0,
            $scaledW,
            $scaledH,
            $this->width,
            $this->height
        );

        /* Step II – if contain: update & end */
        if (!$cover) {
            imagedestroy($this->image);
            $this->image  = $scaled;
            $this->width  = $scaledW;
            $this->height = $scaledH;
            return $this;
        }

        /* Step III – cover: crop to target size */
        $cropX = (int) floor(($scaledW - $dstW) / 2);
        $cropY = (int) floor(($scaledH - $dstH) / 2);

        $final = $this->_createBlank($dstW, $dstH);
        imagecopy(
            $final,
            $scaled,
            0,
            0,            // to (0,0)
            $cropX,
            $cropY,  // from
            $dstW,
            $dstH
        );

        // update state
        imagedestroy($this->image);
        imagedestroy($scaled);
        $this->image  = $final;
        $this->width  = $dstW;
        $this->height = $dstH;
        return $this;
    }

    /* =========================================================
    * Helper: create blank canvas, support alpha PNG/GIF
    * =======================================================*/
    private function _createBlank(int $w, int $h)
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

    /* =========================================================
    * Helper: scale "raw" without keeping aspect ratio
    * =======================================================*/
    private function _resampleTo(int $w, int $h)
    {
        $dst = $this->_createBlank($w, $h);
        imagecopyresampled(
            $dst,
            $this->image,
            0,
            0,
            0,
            0,
            $w,
            $h,
            $this->width,
            $this->height
        );
        imagedestroy($this->image);
        $this->image  = $dst;
        $this->width  = $w;
        $this->height = $h;
        return $this;
    }

    /* ------------------------------
       Convert image format
       ------------------------------ */
    public function convert($format, $quality = 90)
    {
        $format = strtolower($format);
        if (!in_array($format, ['jpg', 'png', 'gif', 'webp'])) {
            throw new \Exception("Format not supported: $format");
        }
        $this->type = $format;
        return $this;
    }

    /* ------------------------------
       Save image to file
       ------------------------------ */
    public function save($destination, $quality = 90)
    {
        $saved = false;
        switch ($this->type) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($this->image, $destination, $quality);
                break;
            case 'png':
                $pngQuality = round((100 - $quality) / 10);
                $saved = imagepng($this->image, $destination, $pngQuality);
                break;
            case 'gif':
                $saved = imagegif($this->image, $destination);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($this->image, $destination, $quality);
                } else {
                    throw new \Exception("WebP support not available.");
                }
                break;
            default:
                throw new \Exception("Save format not supported: {$this->type}");
        }
        return $saved;
    }

    /* ------------------------------
       Output to Base64
       ------------------------------ */
    public function output($format = null, $quality = 90)
    {
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

    /* ------------------------------
       Memory cleanup
       ------------------------------ */
    public function destroy()
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }


    // xoiupdate for image crawling
    // loadFromContent method needs to be added to iMagify, for example:
    public static function loadFromContent($content)
    {
        $instance = new self();
        $instance->image = imagecreatefromstring($content);
        if (!$instance->image) {
            throw new \Exception("Cannot create image from downloaded data");
        }
        $instance->width = imagesx($instance->image);
        $instance->height = imagesy($instance->image);
        $instance->type = 'jpg'; // default, or can check MIME
        return $instance;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }
}
