<?php
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

// render img tag (lazy load).... title, alt, src, class, style
if (!function_exists('_img')) {
    function _img($src, $title = '', $lazy = true, $class = '', $style = '', $width = '', $height = '', $id = '')
    {
        // If no image source then return empty string
        if (empty($src)) {
            return '';
        }

        // Create attributes for alt and title, escape special characters
        $attr_alt   = !empty($title) ? ' alt="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_title = !empty($title) ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_id    = !empty($id)  ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';

        // Process class, style, width, height attributes (escape if needed)
        $attr_class = !empty($class) ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_style = !empty($style) ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_width = !empty($width) ? ' width="' . htmlspecialchars($width, ENT_QUOTES, 'UTF-8') . '"' : '';
        $attr_height = !empty($height) ? ' height="' . htmlspecialchars($height, ENT_QUOTES, 'UTF-8') . '"' : '';

        if ($lazy) {
            // If lazy load is enabled, add "lazyload" class
            if (!empty($class)) {
                $class .= ' lazyload';
            } else {
                $class = 'lazyload';
            }
            $attr_class = ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';

            // Create img tag with data-src instead of src for lazy load
            $img_tag = '<img data-src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
                . $attr_alt . $attr_title . $attr_class . $attr_style . $attr_width . $attr_height . $attr_id . '>';
            // Create fallback with <noscript> tag so browsers without JS support can still display images
            $noscript_tag = '<noscript><img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
                . $attr_alt . $attr_title . $attr_class . $attr_style . $attr_width . $attr_height . $attr_id . '></noscript>';
            return $img_tag . $noscript_tag;
        } else {
            // If not using lazy load, create normal img tag with src
            return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"'
                . $attr_alt . $attr_title . $attr_class . $attr_style . $attr_width . $attr_height . $attr_id . '>';
        }
    }
}


if (!function_exists('addSizeToPath')) {
    // Function to add size before file extension
    function addSizeToPath($path, $size)
    {
        $dotIndex = strrpos($path, '.'); //strrpos # strpos . No find dau . cuoi cung
        if ($dotIndex !== false) {
            $filePathWithoutExt = substr($path, 0, $dotIndex);
            $fileExt = substr($path, $dotIndex);
            return $filePathWithoutExt . '_' . $size . $fileExt;
        }
        return $path; // Return original path if file extension not found
    }
}
if (!function_exists('img_square')) {
    // Function to handle square image path
    function img_square($item)
    {
        if (is_object($item)) {
            $item = (array)$item;
        }
        if (isset($item['path']) && !empty($item['path'])) {
            if (isset($item['resize']) && strpos($item['resize'], '150x150') !== false) {
                return addSizeToPath($item['path'], '150x150');
            }
            return $item['path']; // Return original path if no need to add
        }
        return '/assets/150x150.webp'; // Default path
    }
}
if (!function_exists('img_vertical')) {
    // Function to handle vertical image path
    function img_vertical($item)
    {
        if (is_object($item)) {
            $item = (array)$item;
        }
        if (isset($item['path']) && !empty($item['path'])) {
            if (isset($item['resize']) && strpos($item['resize'], '333x500') !== false) {
                return addSizeToPath($item['path'], '333x500');
            }
            return $item['path']; // Return original path if no need to add
        }
        return '/assets/333x500.webp'; // Default path
    }
}


if (!function_exists('get_image_size')) {
    function get_image_size($item, $size = 'full')
    {
        $item = json_decode($item, true);
        if (isset($item['path']) && !empty($item['path'])) {
            if ($size == 'full') {
                return $item['path'];
            }
            if (isset($item['resize']) && !empty($item['resize'])) {
                $resizes = explode(';', $item['resize']);
                $config_sizes = config('images_sizes');
                switch ($size) {
                    case 'thumb':
                        if (isset($config_sizes['thumb']) && !empty($config_sizes['thumb'])) {
                            if (in_array($size['thumb'], $resizes)) {
                                return addSizeToPath($item['path'], $size['thumb']);
                            }
                        }
                        break;
                    case 'square':
                        if (isset($config_sizes['square']) && !empty($config_sizes['square'])) {
                            if (in_array($config_sizes['square'], $resizes)) {
                                return addSizeToPath($item['path'], $config_sizes['square']);
                            }
                        }
                        break;
                    case 'vertical':
                        if (isset($config_sizes['vertical']) && !empty($config_sizes['vertical'])) {
                            if (in_array($config_sizes['vertical'], $resizes)) {
                                return addSizeToPath($item['path'], $config_sizes['vertical']);
                            }
                        }
                        break;
                    case 'horizontal':
                        if (isset($config_sizes['horizontal']) && !empty($config_sizes['horizontal'])) {
                            if (in_array($config_sizes['horizontal'], $resizes)) {
                                return addSizeToPath($item['path'], $config_sizes['horizontal']);
                            }
                        }
                        break;
                    default:
                        if (in_array($size, $resizes)) {
                            return addSizeToPath($item['path'], $size);
                        }
                        break;
                }
            }
            return $item['path'];
        }
        return '/assets/333x500.webp'; // Default path

    }
}

// add function to get img path
if (!function_exists('get_image_full')) {
    function get_image_full($item)
    {
        return get_image_size($item, 'full');
    }
}
if (!function_exists('get_thumbnail')) {
    function get_thumbnail($item)
    {
        return get_image_size($item, 'thumb');
    }
}
if (!function_exists('get_image_square')) {
    function get_square($item)
    {
        return get_image_size($item, 'square');
    }
}
if (!function_exists('get_image_vertical')) {
    function get_image_vertical($item)
    {
        return get_image_size($item, 'vertical');
    }
}
if (!function_exists('get_image_horizontal')) {
    function get_image_horizontal($item)
    {
        return get_image_size($item, 'horizontal');
    }
}
