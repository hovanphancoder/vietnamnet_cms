<?php
use System\Libraries\Shortcode;
if (!function_exists('do_shortcode')) {
    function do_shortcode($name, ...$params)
    {
        return Shortcode::run($name, ...$params);
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($name, $callback)
    {
        return Shortcode::register($name, $callback);
    }
}

if (!function_exists('remove_shortcode')) {
    function remove_shortcode($name)
    {
        if (Shortcode::exists($name)) {
            $ref = new \ReflectionClass(Shortcode::class);
            $prop = $ref->getProperty('handlers');
            $prop->setAccessible(true);
            $handlers = $prop->getValue();
            unset($handlers[$name]);
            $prop->setValue(null, $handlers);
            return true;
        }
        return false;
    }
}
