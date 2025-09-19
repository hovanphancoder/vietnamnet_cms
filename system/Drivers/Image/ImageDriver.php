<?php

namespace System\Drivers\Image;

interface ImageDriver
{
    public static function load($filename);
    public static function loadFromBase64($base64String);
    public static function loadFromContent($content);
    public function resize($width, $height, $maintainAspect = true, $cover = false);
    public function crop($x, $y, $cropWidth, $cropHeight);
    public function cropByRatio($ratioWidth, $ratioHeight);
    public function addWatermark($watermarkFile, $position = 'bottom-right', $padding = 10, $opacity = 100);
    public function convert($format, $quality = 90);
    public function save($destination, $quality = 90);
    public function output($format = null, $quality = 90);
    public function destroy();
    public function getWidth();
    public function getHeight();
    public function getType();
}
 