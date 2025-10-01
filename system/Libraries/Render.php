<?php

namespace System\Libraries;

use System\Core\AppException;
use Exception;
use MatthiasMullie\Minify;

// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

class Render
{
    // Name of the theme
    private static $themeName;
    // Path to theme directory
    private static $themePath;
    // Path to public/assets directory
    private static $assetsPath;

    // Views tracking for debugbar
    private static $views = [];
    /*
     * Manage assets with structure:
     * assets[area][asset_type][location]
     * asset_type: 'css', 'js', 'inlineCss', 'inlineJs'
     */
    protected static $assets = [
        'frontend' => [
            'css'       => ['head' => [], 'footer' => []],
            'js'        => ['head' => [], 'footer' => []],
            'inlineCss' => ['head' => [], 'footer' => []],
            'inlineJs'  => ['head' => [], 'footer' => []],
        ],
        'backend'  => [
            'css'       => ['head' => [], 'footer' => []],
            'js'        => ['head' => [], 'footer' => []],
            'inlineCss' => ['head' => [], 'footer' => []],
            'inlineJs'  => ['head' => [], 'footer' => []],
        ],
    ];

    /**
     * Initialize and load theme configuration only once
     */
    private static function init()
    {
        if (self::$themeName === null || self::$themePath === null) {
            // Get theme configuration from config file
            // Save theme name and theme path
            self::$themeName = APP_THEME_NAME;

            self::$themePath = APP_THEME_PATH;
            if (self::$assetsPath === null) {
                self::$assetsPath = PATH_ROOT . '/public/assets/';
            }
        }
    }

    /**
     * Get the name of the theme
     *
     * @return string Theme name
     */
    private static function _name()
    {
        self::init();
        return self::$themeName;
    }

    /**
     * Get the path of the theme
     *
     * @return string Path to theme directory
     */
    private static function _path_theme()
    {
        self::init();
        return self::$themePath;
    }

    // /**
    //  * Get the path of a component in themes
    //  *
    //  * @param string $component Name of the component to get path
    //  * @return string Path to the component
    //  */
    // public static function _path_component($component) {
    //     return self::_path_theme() . 'component/';
    // }

    /**
     * Get the path of the theme folder by controller
     * For example: controller Home then theme folder is home/
     *
     * @param string $controller Name of the controller
     * @return string Path to the theme folder of the controller
     */
    public static function _path_controller($controller)
    {
        return self::_path_theme() . strtolower($controller) . '/';
    }

    /**
     * Track view for debugbar
     *
     * @param string $type Type of view (layout, component, view)
     * @param string $name Name/path of the view
     * @param string $fullPath Full file path
     * @param array $data Data passed to view
     */
    public static function trackView($type, $name, $fullPath, $data = [], $duration = 0)
    {
        self::$views[] = [
            'type' => $type,
            'name' => $name,
            'path' => $fullPath,
            'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            'file_modified' => file_exists($fullPath) ? filemtime($fullPath) : 0,
            'data_keys' => array_keys($data),
            'data_count' => count($data),
            'duration_ms' => $duration > 0 ? round($duration, 3) : null,
            'render_time' => microtime(true)
        ];
    }

    /**
     * Get tracked views for debugbar
     *
     * @return array
     */
    public static function getViews()
    {
        return self::$views;
    }


    /**
     * Render the entire layout and view with data.
     *
     * @param string $layout Name of the layout to load (e.g.: 'layout' or 'layout2')
     * @param array $data Data passed to the view
     * @throws \Exception
     */
    public static function render($layout, $data = [])
    {
        self::init(); // Ensure configuration is loaded
        if (!empty($data['view'])) {
            $viewPath = self::_path_theme() . $data['view'] . '.php';
            if (!file_exists($viewPath)) {
                throw new AppException("View '{$data['view']}' not found at Path: '{$viewPath}'.");
            }
            // Add view path to data to pass to layout
            $data['view'] = $viewPath;
        }
        $layoutPath = self::_path_theme() . $layout . '.php';
        if (!file_exists($layoutPath)) {
            throw new AppException("Layout '{$layout}' not found at Path: '{$layoutPath}'.");
        }
        // Pass data to view
        extract($data);
        // Start buffer to store output in string
        ob_start();
        // Call main layout and display content
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) $___start_time = microtime(true);
        require_once $layoutPath;
        $__html = ob_get_clean();

        // Track AFTER render with duration
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $tracked = is_array($data) ? $data : [];
            self::trackView('layout', $layout, $layoutPath, $tracked, (microtime(true) - $___start_time) * 1000);
            if (!empty($viewPath)) {
                $viewTracked = is_array($data) ? $data : [];
                self::trackView('view', $data['view'], $viewPath, $viewTracked, (microtime(true) - $___start_time) * 1000);
            }
        }

        return $__html;  // Return string
    }

    /**
     * Render the entire layout and view with data
     *
     * @param string $layout Name of the layout to load (e.g.: 'layout' or 'layout2')
     * @param array $data Data passed to the view
     * @throws \Exception
     */
    public static function html($layout, $data = [])
    {
        self::init(); // Ensure configuration is loaded
        $layoutPath = self::_path_theme() . $layout . '.php';
        if (!file_exists($layoutPath)) {
            throw new AppException("Layout '{$layout}' not found at Path: '{$layoutPath}'.");
        }
        extract($data);
        ob_start();
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) $___start_time = microtime(true);
        require_once $layoutPath;
        $html = ob_get_clean();
        // Track layout AFTER render with duration
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            $tracked = $data;
            if (!is_array($tracked)) { $tracked = []; }
            self::trackView('layout', $layout, $layoutPath, $tracked, (microtime(true) - $___start_time) * 1000);
        }
        if (APP_DEBUGBAR && (!defined('APP_DEBUGBAR_SKIP') || !APP_DEBUGBAR_SKIP) && stripos($html, '</body>') !== false  && strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
            $debugBarHtml = self::component('Common/Debugbar/debugbar');
            $html = str_replace('</body>', $debugBarHtml . '</body>', $html);
        }
        return $html;
    }

    /**
     * Render a specific component and return as string
     *
     * @param string $component Name of the component to render (e.g.: 'header', 'footer')
     * @param array $data Data passed to the component
     * @return string Rendered component result
     * @throws \Exception
     */
    public static function component($component, $data = [])
    {
        self::init(); // Ensure configuration is loaded

        $componentPath = self::_path_theme() . $component . '.php';

        if (!file_exists($componentPath)) {
            throw new \Exception("Component '{$component}' does not exist at path '{$componentPath}'.");
        }
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            self::trackView('component', $component, $componentPath, $data, 1);
        }

        // Start buffer to store output
        // Pass data to component
        extract($data);
        ob_start();
        require $componentPath;
        $componentHtml = ob_get_clean();        
        return $componentHtml;
    }


    /**
     * Block method: Load Block data and Render to html
     *
     * @param string $blockName Name of the Block, can be capitalized or lowercase
     * @param array $data Additional props parameters of the Block, if not passed will use Default
     *
     * @return string HTML data rendered from the Block
     */
    public static function block($blockName, $data = [])
    {
        $blockFolder = ucfirst($blockName);
        if (strpos($blockName, '\\') !== false) {
            $blockName = explode("\\", $blockName);
            $blockName = ucfirst(end($blockName));
        } else {
            $blockName = ucfirst($blockName);
        }
        $blockClass = "\App\Blocks\\" . $blockFolder . "\\" . $blockName . "Block";
        if (class_exists($blockClass)) {
            $block = new $blockClass();
            $block->setProps($data);
            $block->render();
            return $block;
        } else {
            throw new AppException("Block class $blockClass does not exist.");
        }
    }

    public static function getblock($blockName)
    {
        $blockName = ucfirst($blockName);
        $blockClass = "\App\Blocks\\" . $blockName . "\\" . $blockName . "Block";
        if (class_exists($blockClass)) {
            $block = new $blockClass();
            return $block;
        } else {
            return null;
        }
    }

    /**
     * Get data returned from block via handleData() without rendering HTML
     *
     * @param string $blockName Block name, e.g.: 'Frontend\Sliders\SliderPost'
     * @param array $props Props data passed to block
     * @return array|null
     */
    public static function getDataFromBlock($blockName, $props = [])
    {
        $blockFolder = ucfirst($blockName);
        if (strpos($blockName, '\\') !== false) {
            $parts = explode("\\", $blockName);
            $blockClassName = ucfirst(end($parts));
            $blockFolder = implode("\\", array_map('ucfirst', $parts));
        } else {
            $blockClassName = ucfirst($blockName);
            $blockFolder = $blockClassName;
        }

        $blockClass = "\\App\\Blocks\\{$blockFolder}\\{$blockClassName}Block";

        if (class_exists($blockClass)) {
            $block = new $blockClass();
            $block->setProps($props);
            return method_exists($block, 'handleData') ? $block->handleData() : null;
        }

        return null;
    }


    ////////////////////// ASSET MANAGEMENT (CSS, JS) //////////////////////

    /**
     * Add asset file (css or js) with options.
     *
     * @param string $assetType 'css' or 'js'
     * @param string $file      File name (relative path from assets folder in view)
     * @param array  $options   Options array including:
     *                          - 'area': (default 'frontend')
     *                          - 'location': (default 'head' or 'footer')
     */
    public static function asset($assetType, $file, $options = [])
    {
        self::init();
        $assetType = strtolower($assetType);
        if (!in_array($assetType, ['css', 'js'])) {
            throw new AppException("Invalid asset type: $assetType");
        }
        $area = $options['area'] ?? 'frontend';
        $location = $options['location'] ?? 'head';
        if (!in_array($location, ['head', 'footer'])) {
            $location = 'head';
        }
        if (!isset(self::$assets[$area])) {
            self::$assets[$area] = [
                'css'       => ['head' => [], 'footer' => []],
                'js'        => ['head' => [], 'footer' => []],
                'inlineCss' => ['head' => [], 'footer' => []],
                'inlineJs'  => ['head' => [], 'footer' => []],
            ];
        }

        self::$assets[$area][$assetType][$location][] = $file;
    }

    /**
     * Add inline content for asset (css or js) with options.
     *
     * @param string $assetType 'css' or 'js'
     * @param string $content   Inline content to add.
     * @param array  $options   Options array including:
     *                          - 'area': (default 'frontend')
     *                          - 'location': (default 'head' or 'footer')
     */
    public static function inline($assetType, $content, $options = [])
    {
        self::init();
        $assetType = strtolower($assetType);
        if (!in_array($assetType, ['css', 'js'])) {
            throw new AppException("Invalid inline asset type: $assetType");
        }
        $area = $options['area'] ?? 'frontend';
        $location = $options['location'] ?? 'head';
        if (!in_array($location, ['head', 'footer'])) {
            $location = 'head';
        }
        $key = ($assetType === 'css') ? 'inlineCss' : 'inlineJs';
        if (!isset(self::$assets[$area])) {
            self::$assets[$area] = [
                'css'       => ['head' => [], 'footer' => []],
                'js'        => ['head' => [], 'footer' => []],
                'inlineCss' => ['head' => [], 'footer' => []],
                'inlineJs'  => ['head' => [], 'footer' => []],
            ];
        }
        self::$assets[$area][$key][$location][] = $content;
    }

    /**
     * Output <link>/<script> tags & inline assets exactly as registered
     * NO merging – NO compression.
     *
     * @param string $location 'head' | 'footer'
     * @param string $area     'frontend' | 'backend'
     * @return string
     */
    public static function renderAsset($location = 'head', $area = 'frontend')
    {
        self::init();
        $output = '';

        // ---------- helper build URL ----------
        $buildUrl = function (string $file) use ($area) {
            // Absolute URL or data URI → return as is
            if (preg_match('#^(https?:)?//|^/|^data:#i', $file)) {
                return $file;
            }
            // Relative path → combine with theme directory
            return public_url('themes/' . self::$themeName . '/' . ucfirst($area) . '/' .  'assets/' . ltrim($file, '/'));
        };
        // ---------- CSS ----------
        if (!empty(self::$assets[$area]['css'][$location])) {
            foreach (self::$assets[$area]['css'][$location] as $file) {
                if ($file === '') {
                    continue;                                // skip empty items
                }
                $output .= '<link rel="stylesheet" href="' . $buildUrl($file) . '">' . PHP_EOL;
            }
        }
        // ---------- JS ----------
        if (!empty(self::$assets[$area]['js'][$location])) {
            foreach (self::$assets[$area]['js'][$location] as $file) {
                if ($file === '') {
                    continue;
                }
                $output .= '<script src="' . $buildUrl($file) . '" defer></script>' . PHP_EOL;
            }
        }
        // ---------- INLINE CSS ----------
        if (!empty(self::$assets[$area]['inlineCss'][$location])) {
            $output .= '<style>' . implode("\n", self::$assets[$area]['inlineCss'][$location]) . '</style>' . PHP_EOL;
        }
        // ---------- INLINE JS ----------
        if (!empty(self::$assets[$area]['inlineJs'][$location])) {
            $output .= '<script>' . implode("\n", self::$assets[$area]['inlineJs'][$location]) . '</script>' . PHP_EOL;
        }
        return $output;
    }


    /**
     * Pagination method: create Previous/Next pagination
     * 
     * @param string $base_url Base URL for pagination
     * @param int $current_page Current page number
     * @param bool $is_next Whether there is a next page
     * @param array $query_params Other query parameters to keep on URL
     * @param array $custom_names Custom variable names in query string (page, ...)
     * 
     * @return string Previous/Next pagination HTML
     */
    public static function pagination($base_url, $current_page, $is_next, $query_params = ['limit' =>  10], $custom_names = [])
    {
        self::init();

        // Default variable names for pagination
        $default_names = [
            'page' => 'page',
        ];

        // Combine custom variables with default variable names
        $custom_names = array_merge($default_names, $custom_names);

        // Create query string for other parameters (excluding page)
        $query_string = http_build_query($query_params);

        // Remove ?page=1 if currently on page 1
        if ($current_page == 1) {
            $page_query_string = $query_string ? '?' . $query_string : ''; // No ? if no other query string
        } else {
            $page_query_string = '?' . $custom_names['page'] . '=' . $current_page;
            if ($query_string) {
                $page_query_string .= '&' . $query_string;
            }
        }

        // URLs for previous and next pages
        $prev_page_url = $current_page > 2 ? $base_url . '?' . $custom_names['page'] . '=' . ($current_page - 1) . '&' . $query_string : ($query_string ? $base_url . '?' . $query_string : $base_url);
        $next_page_url = $base_url . '?' . $custom_names['page'] . '=' . ($current_page + 1) . '&' . $query_string;

        // Remove trailing & characters
        $prev_page_url = rtrim($prev_page_url, '&');
        $next_page_url = rtrim($next_page_url, '&');

        $data = [
            'base_url'       => $base_url,
            'current_page'   => $current_page,
            'is_next'        => $is_next,
            'prev_page_url'  => $prev_page_url,
            'next_page_url'  => $next_page_url,
            'custom_names'   => $custom_names,
            'query_params'   => $query_string
        ];

        // Use pagination2.php view to render pagination HTML
        return self::component('Common/Pagination/pagination', $data);
    }


    /**
     * Render an input from field
     * 
     * @param array $field Field to render
     * @param mixed $field_value Field value (can be from database or request)
     * @param string|null $error_message Error message if any
     * @param string|null $prefix Prefix for nested fields (e.g., "parent[0]")
     * @param int|null $index Index for array fields
     * @return string Input HTML string
     * @throws \Exception
     */
    public static function input($field, $field_value = null, $error_message = null, $prefix = null, $index = null)
    {
        self::init(); // Ensure configuration is loaded
        $html = '';
        // Get field type and convert to lowercase
        $field_type = strtolower($field['type'] ?? 'text');
        // Replace spaces and invalid characters in filename
        $field_type = strtolower(preg_replace('/[^a-z0-9]+/', '_', $field_type));
        // Path to corresponding input file
        $inputPath = self::_path_theme() . 'Common/Input/' . $field_type . '.php';
        if (!file_exists($inputPath)) {
            throw new \Exception("Input type '{$field_type}' does not exist at path '{$inputPath}'.");
        }
        if (!isset($field['field_name']) && isset($field['name'])) {
            $field['field_name'] = $field['name'];
        }

        // Build field name with prefix and index for nested structures
        $field_name = $field['field_name'] ?? '';
        if ($prefix && $index !== null) {
            $field_name = $prefix . '[' . $index . '][' . $field_name . ']';
        } elseif ($prefix) {
            $field_name = $prefix . '[' . $field_name . ']';
        }

        // Pre-process common variables
        $inputData = [
            'id' => 'field_' . (isset($field['id']) ? xss_clean($field['id']) : uniqid()),
            'type' => $field_type,
            'label' => isset($field['label']) ? xss_clean($field['label']) : '',
            'name' => $field_name,
            'field_name' => $field['field_name'] ?? '', // Original field name without prefix
            'default_value' => $field['default_value'] ?? '',
            'value' => isset($field_value) ? $field_value : ($field['default_value'] ?? ''),
            'description' => isset($field['description']) ? xss_clean($field['description']) : '',
            'autofill'  => isset($field['autofill']) ? xss_clean($field['autofill']) : null,
            'autofill_type' =>  isset($field['autofill_type']) ? xss_clean($field['autofill_type']) : 'match',
            'required' => isset($field['required']) && $field['required'],
            'visibility' => isset($field['visibility']) && !$field['visibility'] ? false : true,
            'css_class' => isset($field['css_class']) ? xss_clean($field['css_class']) : '',
            'placeholder' => isset($field['placeholder']) ? xss_clean($field['placeholder']) : '',
            'order' => isset($field['order']) ? (int) $field['order'] : 0,
            'min' => isset($field['min']) ? (int) $field['min'] : null,
            'max' => isset($field['max']) ? (int) $field['max'] : null,
            'width_value' => isset($field['width_value']) ? (int) $field['width_value'] : 100,
            'width_unit' => isset($field['width_unit']) ? $field['width_unit'] : '%',
            'position' => isset($field['position']) ? $field['position'] : 'left',
            'options' => $field['options'] ?? [],
            'rows' => isset($field['rows']) ? (int) $field['rows'] : 3,
            'allow_types' => $field['allow_types'] ?? [],
            'max_file_size' => isset($field['max_file_size']) ? (float) $field['max_file_size'] : null,
            'multiple' => isset($field['multiple']) && $field['multiple'],
            'multiple_server' => isset($field['multiple_server']) && $field['multiple_server'],
            'servers' => isset($field['servers']) ? $field['servers'] : array(),
            'post_type_reference' => isset($field['post_type_reference']) ? xss_clean($field['post_type_reference']) : null,
            'post_status_filter' => isset($field['post_status_filter']) ? xss_clean($field['post_status_filter']) : null,
            'error_message' => isset($error_message) ? $error_message : '',
            'data' => !empty($field['data']) ? $field['data'] : [],
            'prefix' => $prefix,
            'index' => $index,
        ];
        if (isset($field['step']) && $field['step'] > 0) {
            $inputData['step'] = $field['step'];
        }
        if (isset($field['layouts']) && count($field['layouts']) > 0) {
            $inputData['layouts'] = $field['layouts'];
            if (isset($field['button_label'])) {
                $inputData['button_label'] = $field['button_label'];
            }
            if (isset($field['min_layouts'])) {
                $inputData['min_layouts'] = $field['min_layouts'];
            }
            if (isset($field['max_layouts'])) {
                $inputData['max_layouts'] = $field['max_layouts'];
            }
        }

        // Get server max upload configuration
        $uploadMaxFilesize = _bytes(ini_get('upload_max_filesize'));
        $postMaxSize = _bytes(ini_get('post_max_size'));
        $maxUploadSize = min($uploadMaxFilesize, $postMaxSize);
        if ($inputData['max_file_size'] === null || $inputData['max_file_size'] * 1024 * 1024 > $maxUploadSize) {
            $inputData['max_file_size'] = $maxUploadSize;
            $inputData['max_file_size'] = ceil($inputData['max_file_size'] / (1024 * 1024));
        }

        if (strtolower($field['type']) == 'image') {
            $inputData['autocrop'] = $field['autocrop'] ?? 0;
            $inputData['watermark'] = $field['watermark'] ?? 0;
            $inputData['watermark_img'] = $field['watermark_img'] ?? '';
            $inputData['resizes'] = $field['resizes'] ?? [];
        }

        // Handle repeater fields
        if ($field_type == 'repeater') {
            $inputData['fields'] = $field['fields'] ?? [];
            $inputData['level'] = empty($field['level']) ? 1 : $field['level'];
        }

        // Handle flexible fields
        if ($field_type == 'flexible') {
            $inputData['layouts'] = $field['layouts'] ?? [];
            $inputData['button_label'] = $field['button_label'] ?? 'Add Layout';
            $inputData['min_layouts'] = $field['min_layouts'] ?? null;
            $inputData['max_layouts'] = $field['max_layouts'] ?? null;
        }

        $inputData['is_repeater'] = $field['is_repeater'] ?? false;

        // Start buffer to store output
        ob_start();
        extract($inputData);
        require $inputPath;
        $html .= ob_get_clean();
        return $html;
    }
}
