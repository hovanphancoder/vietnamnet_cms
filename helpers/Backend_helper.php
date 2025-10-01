<?php
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}
if(!function_exists('indexByID')) {
    function indexByID($data) {
        if(is_string($data)) {
            $data = json_decode($data, true);
        }
        $result = [];
        if(empty($data)) {
            return $result;
        }
        foreach ($data as $item) {
            if(isset($item['id'])) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
}
if(!function_exists('convers_array')) {
    function convers_array($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        } elseif (is_object($data)) {
            $data = (array)$data;
        } elseif (!is_array($data)) {
            $data = [];
        }
        return $data;
    }
}
if (!function_exists('get_template')) {
    function get_template($templateName, $data = [], $area = 'Frontend')
    {
        // Kiểm tra xem file template có tồn tại không
        $templateFile = APP_THEME_PATH . ucfirst($area) . '/' . $templateName . '.php';
        if (file_exists($templateFile)) {
            // Tạo một scope riêng cho template
            (function($data) use ($templateFile) {
                extract($data); // Chuyển mảng data thành các biến
                include $templateFile;
            })($data);
        } else {
            echo '<!-- Template file not found: ' . $templateName . ' -->';
        }
    }
}