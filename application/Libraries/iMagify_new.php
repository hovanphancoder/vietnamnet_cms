<?php

namespace App\Libraries;

use Exception;
use GdImage;

class iMagify_2 {
    private ImageDriver $driver;
    private array $options;
    private array $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private ?GdImage $image = null;
    private int $width = 0;
    private int $height = 0;
    private string $type = '';

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'quality' => 90,
            'webp_quality' => 80,
            'webp_enabled' => true,
            'formatname' => '{name}_{size}.{ext}',
            'watermark' => [
                'enabled' => false,
                'image' => null,
                'position' => 'bottom-right',
                'opacity' => 50,
                'margin' => 10,
                'width' => '100px',
                'height' => '100px',
                'min_width' => '50px',
                'min_height' => '10%',
                'max_width' => '20%',
                'max_height' => '20%'
            ],
            'sizes' => [
                [
                    'name' => 'large',
                    'width' => 1200,
                    'height' => 1200
                ],
                [
                    'name' => 'medium',
                    'width' => 600,
                    'height' => 600
                ],
                [
                    'name' => 'thumbnail',
                    'width' => 300,
                    'height' => 300
                ]
            ],
            'background' => [
                'r' => 255,
                'g' => 255,
                'b' => 255,
                'alpha' => 0
            ],
            'position' => 'center',
            'use_binary' => false,
        ], $options);

        try {
            $this->driver = $this->_driver();
        } catch (Exception $e) {
            throw new ImageProcessorException('Cannot initialize driver: ' . $e->getMessage());
        }
    }

    private function _driver(): ImageDriver {
        return new GDDriver($this->options);
        if(function_exists('exec')){
            return new BinaryDriver($this->options);
        }elseif(extension_loaded('gd')){
            return new GDDriver($this->options);
        }else{
            throw new ImageProcessorException('No suitable driver found (GD or Binary)');
        }
    }

    public static function load($filename)
    {
        $instance = new self();
        $instance->loadImage($filename);
        return $instance;
    }

    public static function loadFromBase64($base64String)
    {
        $instance = new self();
        $base64String = preg_replace('#^data:image/\w+;base64,#i', '', $base64String);
        $data = base64_decode($base64String);
        if ($data === false) {
            throw new ImageProcessorException("Base64 decode failed.");
        }
        $instance->image = imagecreatefromstring($data);
        if (!$instance->image) {
            throw new ImageProcessorException("Cannot create image from Base64 string.");
        }
        $instance->width = imagesx($instance->image);
        $instance->height = imagesy($instance->image);
        $instance->type = 'jpg';
        return $instance;
    }

    private function loadImage($filename)
    {
        if (!file_exists($filename)) {
            throw new ImageProcessorException("File does not exist: $filename");
        }
        $info = getimagesize($filename);
        if (!$info) {
            throw new ImageProcessorException("File is not a valid image: $filename");
        }
        $mime = $info['mime'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed)) {
            throw new ImageProcessorException("Image format not allowed: $mime");
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
                    throw new ImageProcessorException("WebP support not available.");
                }
                break;
            default:
                throw new ImageProcessorException("Image format not supported: $mime");
        }
        if (!$this->image) {
            throw new ImageProcessorException("Cannot load image from file: $filename");
        }
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    public function addWatermark(
        string $watermarkFile,
        string $position = 'bottom-right',
        int $padding = 10,
        int $opacity = 100
    ) {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        imagepng($this->image, $tempFile);
        
        $result = $this->driver->watermark(
            $tempFile,
            $watermarkFile,
            $position,
            $opacity,
            $padding,
            $this->options['watermark']['width'],
            $this->options['watermark']['height'],
            $this->options['watermark']['min_width'],
            $this->options['watermark']['min_height'],
            $this->options['watermark']['max_width'],
            $this->options['watermark']['max_height']
        );
        
        $this->image = imagecreatefrompng($tempFile);
        @unlink($tempFile);
        
        return $result;
    }

    public function crop($x, $y, $cropWidth, $cropHeight)
    {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        imagepng($this->image, $tempFile);
        
        $result = $this->driver->crop($tempFile, $x, $y, $cropWidth, $cropHeight);
        
        $this->image = imagecreatefrompng($tempFile);
        @unlink($tempFile);
        
        return $result;
    }

    public function cropByRatio($ratioWidth, $ratioHeight)
    {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

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

    public function resize(
        int $dstW,
        int $dstH,
        bool $maintainAspect = true,
        bool $cover = false
    ) {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        imagepng($this->image, $tempFile);
        
        $mode = $cover ? 'cover' : ($maintainAspect ? 'contain' : 'fill');
        $result = $this->driver->resize($tempFile, $dstW, $dstH, $mode);
        
        $this->image = imagecreatefrompng($tempFile);
        @unlink($tempFile);
        
        return $result;
    }

    public function convert($format, $quality = 90)
    {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $format = strtolower($format);
        if (!in_array($format, ['jpg', 'png', 'gif', 'webp'])) {
            throw new ImageProcessorException("Format not supported: $format");
        }
        $this->type = $format;
        return $this;
    }

    public function save($destination, $quality = 90)
    {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'img_');
        imagepng($this->image, $tempFile);
        
        $result = $this->driver->save($destination);
        
        @unlink($tempFile);
        
        return $result;
    }

    public function output($format = null, $quality = 90)
    {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
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
                    throw new ImageProcessorException("WebP support not available.");
                }
                break;
            default:
                throw new ImageProcessorException("Output format not supported: $fmt");
        }
        $data = ob_get_contents();
        ob_end_clean();
        return 'data:image/' . $fmt . ';base64,' . base64_encode($data);
    }

    public function destroy()
    {
        if ($this->image) {
            imagedestroy($this->image);
            $this->image = null;
        }
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getType()
    {
        return $this->type;
    }

    public static function loadFromContent($content)
    {
        $instance = new self();
        $instance->image = imagecreatefromstring($content);
        if (!$instance->image) {
            throw new ImageProcessorException("Cannot create image from content");
        }
        $instance->width = imagesx($instance->image);
        $instance->height = imagesy($instance->image);
        $instance->type = 'jpg'; // default, or can check MIME
        return $instance;
    }

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
}

class ImageProcessorException extends Exception {}

interface ImageDriver {
    public function createImage(string $path): bool;
    public function getImageInfo(string $path): array;
    public function crop(string $path, int $x, int $y, int $width, int $height): bool;
    public function resize(string $path, int $width, int $height, string $mode = 'cover'): bool;
    public function rotate(string $path, int $angle): bool;
    public function flip(string $path, string $mode): bool;
    public function filter(string $path, string $filter, array $options = []): bool;
    public function watermark(
        string $path, 
        string $watermarkPath, 
        string $position, 
        int $opacity, 
        int $margin,
        string $width = '100px',
        string $height = '100px',
        string $minWidth = '50px',
        string $minHeight = '10%',
        string $maxWidth = '20%',
        string $maxHeight = '20%'
    ): bool;
    public function optimize(string $path): bool;
    public function convert(string $path, string $format): bool;
    public function save(string $path): bool;
    public function destroy(): bool;
}

class GDDriver implements ImageDriver {
    public ?GdImage $image;
    protected array $options;

    public function __construct(array $options = []) {
        $this->options = $options;
        $this->image = null;
    }

    public function createImage(string $path): bool {
        if (!file_exists($path)) {
            throw new ImageProcessorException("File does not exist: " . $path);
        }
        
        $content = file_get_contents($path);
        if ($content === false) {
            throw new ImageProcessorException("Cannot read file: " . $path);
        }

        $this->image = imagecreatefromstring($content);
        if ($this->image === false) {
            throw new ImageProcessorException("Cannot create image from file: " . $path);
        }

        return true;
    }

    public function getImageInfo(string $path): array {
        if (!file_exists($path)) {
            throw new ImageProcessorException("File does not exist: " . $path);
        }

        $info = getimagesize($path);
        if ($info === false) {
            throw new ImageProcessorException("Cannot read image info from file: " . $path);
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'format' => image_type_to_extension($info[2], false),
            'mime' => $info['mime'],
            'size' => filesize($path)
        ];
    }

    public function crop(string $path, int $x, int $y, int $width, int $height): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $cropped = imagecrop($this->image, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ]);

        if ($cropped === false) {
            throw new ImageProcessorException("Cannot crop image");
        }

        imagedestroy($this->image);
        $this->image = $cropped;
        return true;
    }

    public function resize(string $path, int $width, int $height, string $mode = 'cover'): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $srcWidth = imagesx($this->image);
        $srcHeight = imagesy($this->image);
        $srcRatio = $srcWidth / $srcHeight;
        $dstRatio = $width / $height;

        $srcX = 0;
        $srcY = 0;
        $dstX = 0;
        $dstY = 0;
        $dstWidth = $width;
        $dstHeight = $height;

        switch ($mode) {
            case 'contain':
                if ($srcRatio > $dstRatio) {
                    $newWidth = $width;
                    $newHeight = (int)($width / $srcRatio);
                } else {
                    $newHeight = $height;
                    $newWidth = (int)($height * $srcRatio);
                }
                $dstX = (int)(($width - $newWidth) / 2);
                $dstY = (int)(($height - $newHeight) / 2);
                $dstWidth = $newWidth;
                $dstHeight = $newHeight;
                break;

            case 'cover':
                if ($srcRatio > $dstRatio) {
                    $scale = $height / $srcHeight;
                    $scaledWidth = (int)($srcWidth * $scale);
                    $srcX = (int)(($scaledWidth - $width) / (2 * $scale));
                    $srcWidth = (int)($width / $scale);
                } else {
                    $scale = $width / $srcWidth;
                    $scaledHeight = (int)($srcHeight * $scale);
                    $srcY = (int)(($scaledHeight - $height) / (2 * $scale));
                    $srcHeight = (int)($height / $scale);
                }
                break;
        }

        $resized = imagecreatetruecolor($width, $height);
        
        if (imageistruecolor($this->image)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled(
            $resized,
            $this->image,
            $dstX, $dstY,
            $srcX, $srcY,
            $dstWidth, $dstHeight,
            $srcWidth, $srcHeight
        );

        imagedestroy($this->image);
        $this->image = $resized;
        return true;
    }

    public function rotate(string $path, int $angle): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $rotated = imagerotate($this->image, $angle, 0);
        if ($rotated === false) {
            throw new ImageProcessorException("Cannot rotate image");
        }

        imagedestroy($this->image);
        $this->image = $rotated;
        return true;
    }

    public function flip(string $path, string $mode): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $flipped = imagecreatetruecolor($width, $height);
        if ($flipped === false) {
            throw new ImageProcessorException("Cannot create new image");
        }

        if ($mode === 'horizontal') {
            for ($x = 0; $x < $width; $x++) {
                imagecopy($flipped, $this->image, $width - $x - 1, 0, $x, 0, 1, $height);
            }
        } else {
            for ($y = 0; $y < $height; $y++) {
                imagecopy($flipped, $this->image, 0, $height - $y - 1, 0, $y, $width, 1);
            }
        }

        imagedestroy($this->image);
        $this->image = $flipped;
        return true;
    }

    public function filter(string $path, string $filter, array $options = []): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Image not created yet");
        }

        switch ($filter) {
            case 'blur':
                imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
                break;
            case 'sharpen':
                imagefilter($this->image, IMG_FILTER_SMOOTH, -7);
                break;
            case 'grayscale':
                imagefilter($this->image, IMG_FILTER_GRAYSCALE);
                break;
            case 'sepia':
                imagefilter($this->image, IMG_FILTER_GRAYSCALE);
                imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0, 0);
                break;
            default:
                throw new ImageProcessorException("Invalid filter: " . $filter);
        }
        return true;
    }

    public function watermark(
        string $path, 
        string $watermarkPath, 
        string $position, 
        int $opacity, 
        int $margin,
        string $width = '100px',
        string $height = '100px',
        string $minWidth = '50px',
        string $minHeight = '10%',
        string $maxWidth = '20%',
        string $maxHeight = '20%'
    ): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Chưa tạo image");
        }

        if (!file_exists($watermarkPath)) {
            throw new ImageProcessorException("File watermark không tồn tại: " . $watermarkPath);
        }

        $watermark = imagecreatefrompng($watermarkPath);
        if ($watermark === false) {
            throw new ImageProcessorException("Không thể tạo image từ file watermark");
        }

        $wmWidth = imagesx($watermark);
        $wmHeight = imagesy($watermark);
        $imgWidth = imagesx($this->image);
        $imgHeight = imagesy($this->image);

        // Calculate watermark size
        $wmWidth = $this->_parseSize($width, $imgWidth);
        $wmHeight = $this->_parseSize($height, $imgHeight);
        $minW = $this->_parseSize($minWidth, $imgWidth);
        $minH = $this->_parseSize($minHeight, $imgHeight);
        $maxW = $this->_parseSize($maxWidth, $imgWidth);
        $maxH = $this->_parseSize($maxHeight, $imgHeight);

        $wmWidth = max($minW, min($maxW, $wmWidth));
        $wmHeight = max($minH, min($maxH, $wmHeight));

        $resized = imagecreatetruecolor($wmWidth, $wmHeight);
        if ($resized === false) {
            imagedestroy($watermark);
            throw new ImageProcessorException("Không thể tạo image mới");
        }

        imagecopyresampled(
            $resized, 
            $watermark, 
            0, 0, 0, 0, 
            $wmWidth, $wmHeight, 
            imagesx($watermark), 
            imagesy($watermark)
        );

        // Calculate position
        $x = $margin;
        $y = $margin;

        switch ($position) {
            case 'top-right':
                $x = $imgWidth - $wmWidth - $margin;
                break;
            case 'bottom-left':
                $y = $imgHeight - $wmHeight - $margin;
                break;
            case 'bottom-right':
                $x = $imgWidth - $wmWidth - $margin;
                $y = $imgHeight - $wmHeight - $margin;
                break;
            case 'center':
                $x = ($imgWidth - $wmWidth) / 2;
                $y = ($imgHeight - $wmHeight) / 2;
                break;
        }

        imagecopymerge(
            $this->image, 
            $resized, 
            $x, $y, 0, 0, 
            $wmWidth, $wmHeight, 
            $opacity
        );

        imagedestroy($watermark);
        imagedestroy($resized);
        return true;
    }

    private function _parseSize(string $size, int $base): int {
        if (strpos($size, '%') !== false) {
            return (int)($base * (int)$size / 100);
        }
        return (int)$size;
    }

    public function optimize(string $path): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Chưa tạo image");
        }

        imagepalettetotruecolor($this->image);
        return true;
    }

    public function convert(string $path, string $format): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Chưa tạo image");
        }

        if ($format === 'webp') {
            $this->_createDirectory($path);
            imagewebp($this->image, $path, $this->options['webp_quality'] ?? 80);
        }
        return true;
    }

    public function save(string $path): bool {
        if ($this->image === null) {
            throw new ImageProcessorException("Chưa tạo image");
        }

        $this->_createDirectory($path);
        
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->image, $path, $this->options['quality'] ?? 90);
                break;
            case 'png':
                imagepng($this->image, $path, 9);
                break;
            case 'gif':
                imagegif($this->image, $path);
                break;
            case 'webp':
                imagewebp($this->image, $path, $this->options['webp_quality'] ?? 80);
                break;
            default:
                throw new ImageProcessorException("Định dạng không được hỗ trợ: " . $ext);
        }
        return true;
    }

    public function destroy(): bool {
        if ($this->image !== null) {
            imagedestroy($this->image);
            $this->image = null;
        }
        return true;
    }

    private function _createDirectory(string $path): void {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new ImageProcessorException("Không thể tạo thư mục: " . $dir);
            }
        }
    }
}

class BinaryDriver implements ImageDriver {
    private array $options;
    private string $binPath;
    private array $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(array $options = []) {
        $this->options = $options;
        $this->binPath = dirname(__FILE__) . '/bin/';
    }

    private function _getBinaryPath(string $binary): string {
        $os = strtolower(PHP_OS);
        $ext = '';
        
        if (strpos($os, 'win') !== false) {
            $ext = '.exe';
        } elseif (strpos($os, 'darwin') !== false) {
            $ext = '-mac';
        } elseif (strpos($os, 'linux') !== false) {
            $ext = '-linux';
        } elseif (strpos($os, 'freebsd') !== false) {
            $ext = '-fbsd';
        }

        $path = $this->binPath . $binary . $ext;
        if (!file_exists($path)) {
            throw new ImageProcessorException("Binary không tồn tại: " . $path);
        }
        return $path;
    }

    private function _executeCommand(string $command): bool {
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new ImageProcessorException("Lỗi khi thực thi lệnh: " . implode("\n", $output));
        }
        return true;
    }

    public function createImage(string $path): bool {
        if (!file_exists($path)) {
            throw new ImageProcessorException("File không tồn tại: " . $path);
        }
        return true;
    }

    public function getImageInfo(string $path): array {
        if (!file_exists($path)) {
            throw new ImageProcessorException("File không tồn tại: " . $path);
        }

        $info = getimagesize($path);
        if ($info === false) {
            throw new ImageProcessorException("Không thể đọc thông tin file: " . $path);
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'format' => image_type_to_extension($info[2], false),
            'mime' => $info['mime'],
            'size' => filesize($path)
        ];
    }

    public function crop(string $path, int $x, int $y, int $width, int $height): bool {
        $info = $this->getImageInfo($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $command = sprintf(
                '%s -crop %dx%d+%d+%d -outfile %s %s',
                $this->_getBinaryPath('jpegtran'),
                $width,
                $height,
                $x,
                $y,
                escapeshellarg($path),
                escapeshellarg($path)
            );
        } else {
            throw new ImageProcessorException("Crop chỉ hỗ trợ định dạng JPG");
        }

        return $this->_executeCommand($command);
    }

    public function resize(string $path, int $width, int $height, string $mode = 'cover'): bool {
        $info = $this->getImageInfo($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $command = sprintf(
                    '%s -resize %dx%d -outfile %s %s',
                    $this->_getBinaryPath('jpegtran'),
                    $width,
                    $height,
                    escapeshellarg($path),
                    escapeshellarg($path)
                );
                break;
            case 'png':
                $command = sprintf(
                    '%s --force --strip all --size %d --output %s %s',
                    $this->_getBinaryPath('optipng'),
                    $width * $height,
                    escapeshellarg($path),
                    escapeshellarg($path)
                );
                break;
            case 'gif':
                $command = sprintf(
                    '%s --resize %dx%d --output %s %s',
                    $this->_getBinaryPath('gifsicle'),
                    $width,
                    $height,
                    escapeshellarg($path),
                    escapeshellarg($path)
                );
                break;
            default:
                throw new ImageProcessorException("Định dạng không được hỗ trợ: " . $ext);
        }

        return $this->_executeCommand($command);
    }

    public function rotate(string $path, int $angle): bool {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $command = sprintf(
                '%s -rotate %d -outfile %s %s',
                $this->_getBinaryPath('jpegtran'),
                $angle,
                escapeshellarg($path),
                escapeshellarg($path)
            );
        } else {
            throw new ImageProcessorException("Rotate chỉ hỗ trợ định dạng JPG");
        }

        return $this->_executeCommand($command);
    }

    public function flip(string $path, string $mode): bool {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $flip = $mode === 'horizontal' ? '-flip horizontal' : '-flip vertical';
            $command = sprintf(
                '%s %s -outfile %s %s',
                $this->_getBinaryPath('jpegtran'),
                $flip,
                escapeshellarg($path),
                escapeshellarg($path)
            );
        } else {
            throw new ImageProcessorException("Flip chỉ hỗ trợ định dạng JPG");
        }

        return $this->_executeCommand($command);
    }

    public function filter(string $path, string $filter, array $options = []): bool {
        throw new ImageProcessorException("Filter không được hỗ trợ trong BinaryDriver");
    }

    public function watermark(
        string $path, 
        string $watermarkPath, 
        string $position, 
        int $opacity, 
        int $margin,
        string $width = '100px',
        string $height = '100px',
        string $minWidth = '50px',
        string $minHeight = '10%',
        string $maxWidth = '20%',
        string $maxHeight = '20%'
    ): bool {
        throw new ImageProcessorException("Watermark không được hỗ trợ trong BinaryDriver");
    }

    public function optimize(string $path): bool {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $command = sprintf(
                    '%s -optimize -progressive -outfile %s %s',
                    $this->_getBinaryPath('jpegtran'),
                    escapeshellarg($path),
                    escapeshellarg($path)
                );
                break;
            case 'png':
                $command = sprintf(
                    '%s --force --strip all --output %s %s',
                    $this->_getBinaryPath('optipng'),
                    escapeshellarg($path),
                    escapeshellarg($path)
                );
                break;
            case 'gif':
                $command = sprintf(
                    '%s --optimize=3 --output %s %s',
                    $this->_getBinaryPath('gifsicle'),
                    escapeshellarg($path),
                    escapeshellarg($path)
                );
                break;
            default:
                throw new ImageProcessorException("Định dạng không được hỗ trợ: " . $ext);
        }

        return $this->_executeCommand($command);
    }

    public function convert(string $path, string $format): bool {
        if (!in_array($format, $this->supportedFormats)) {
            throw new ImageProcessorException("Định dạng không được hỗ trợ: " . $format);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $newPath = pathinfo($path, PATHINFO_DIRNAME) . '/' . 
                  pathinfo($path, PATHINFO_FILENAME) . '.' . $format;

        if ($format === 'webp') {
            $command = sprintf(
                '%s -q %d %s -o %s',
                $this->_getBinaryPath('cwebp'),
                $this->options['webp_quality'] ?? 80,
                escapeshellarg($path),
                escapeshellarg($newPath)
            );
        } else {
            throw new ImageProcessorException("Chuyển đổi chỉ hỗ trợ định dạng WebP");
        }

        $result = $this->_executeCommand($command);
        if ($result && $newPath !== $path) {
            unlink($path);
            rename($newPath, $path);
        }
        return $result;
    }

    public function save(string $path): bool {
        return $this->optimize($path);
    }

    public function destroy(): bool {
        return true;
    }
}


