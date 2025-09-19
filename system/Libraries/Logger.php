<?php
namespace System\Libraries;
// Check if PATH_ROOT is not defined, prevent direct access
if (!defined('PATH_ROOT')) {
    exit('No direct access allowed.');
}

class Logger {

    /**
     * Write info log.
     */
    public static function info($message, $file = null, $line = null) {
        self::log('INFO', $message, $file, $line);
    }

    /**
     * Write warning log.
     */
    public static function warning($message, $file = null, $line = null) {
        self::log('WARNING', $message, $file, $line);
    }

    /**
     * Write error log.
     */
    public static function error($message, $file = null, $line = null) {
        self::log('ERROR', $message, $file, $line);
    }

    /**
     * Main logging function.
     */
    protected static function log($level, $message, $file = null, $line = null) {
        $logFile = PATH_WRITE . '/logs/logger.log';
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
    }
}
