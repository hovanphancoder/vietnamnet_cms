<?php

namespace System\Libraries;
// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

class Logger
{

    /**
     * Array to store logs for debugbar
     */
    private static $logs = [];

    /**
     * Write info log.
     */
    public static function info($message, $file = null, $line = null)
    {
        self::log('INFO', $message, $file, $line);
    }

    /**
     * Write warning log.
     */
    public static function warning($message, $file = null, $line = null)
    {
        self::log('WARNING', $message, $file, $line);
    }

    /**
     * Write error log.
     */
    public static function error($message, $file = null, $line = null)
    {
        self::log('ERROR', $message, $file, $line);
    }

    /**
     * Write debug log.
     */
    public static function debug($message, $file = null, $line = null)
    {
        self::log('DEBUG', $message, $file, $line);
    }

    /**
     * Main logging function.
     */
    protected static function log($level, $message, $file = null, $line = null)
    {
        $logFile = PATH_WRITE . 'logs/logger.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$level}: {$message}";

        if ($file && $line) {
            $logMessage .= " in {$file} on line {$line}";
        }

        $logMessage .= PHP_EOL;

        // Check and create log directory if it doesn't exist
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        // Write log to file
        @file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Track log for debugbar if enabled
        if (defined('APP_DEBUGBAR') && APP_DEBUGBAR) {
            self::trackLog($level, $message, $file, $line, $timestamp);
        }
    }

    /**
     * Track log for debugbar
     */
    private static function trackLog($level, $message, $file = null, $line = null, $timestamp = null)
    {
        if (!$timestamp) {
            $timestamp = date('Y-m-d H:i:s');
        }

        // Check if message is JSON
        $isJson = false;
        $jsonData = null;
        if (is_string($message)) {
            $decoded = json_decode($message, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $isJson = true;
                $jsonData = $decoded;
            }
        }
        self::$logs[] = [
            'level' => $level,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => $timestamp,
            'is_json' => $isJson,
            'json_data' => $jsonData
        ];
    }

    /**
     * Get logs for debugbar
     */
    public static function getLogs()
    {
        return self::$logs;
    }
}
