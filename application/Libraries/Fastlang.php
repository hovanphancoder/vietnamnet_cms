<?php
namespace App\Libraries;

class Fastlang
{
    // Array to store all loaded translations
    protected static $translations = [];

    // Array to store loaded files
    protected static $load_list = [];

    /**
     * Load language file if not already loaded
     */
    public static function load($file, $lang = APP_LANG)
    {
        $file_key = "{$file}_{$lang}";

        // Check if language file has already been loaded
        if (!isset(self::$load_list[$file_key])) {
            $file_lang = PATH_ROOT . "/languages/{$lang}/" . ucfirst($file) . ".php";
            
            // If file exists, load into $translations
            if (file_exists($file_lang)) {
                $translations = require $file_lang;

                // Merge new translations into main translations array
                self::$translations = array_merge(self::$translations, $translations);

                // Mark this file as loaded
                self::$load_list[$file_key] = true;
            }
        }
    }

    /**
     * Get translation string, return key itself if not found
     */
    public static function _e($key, ...$args)
    {
        $translation = self::$translations[$key] ?? ucfirst($key);
        // Only call replacePlaceholders if $args is not empty
        if (!empty($args)) {
            $translation = self::replacePlaceholders($translation, $args);
        }
        return $translation;
    }

    /**
     * Echo translation string, echo key itself if not found
     */
    public static function _($key, ...$args)
    {
        $translation = self::$translations[$key] ?? ucfirst($key);
        // Only call replacePlaceholders if $args is not empty
        if (!empty($args)) {
            $translation = self::replacePlaceholders($translation, $args);
        }
        echo $translation;
        unset($translation);
    }

    /**
     * Replace placeholders in string with provided values
     */
    protected static function replacePlaceholders($string, $args)
    {
        foreach ($args as $index => $value) {
            $string = str_replace('%' . ($index + 1) . '%', $value, $string);
        }
        return $string;
    }

    /**
     * Reset translations and load list
     */
    public static function reset()
    {
        self::$translations = [];
        self::$load_list = [];
    }
}
