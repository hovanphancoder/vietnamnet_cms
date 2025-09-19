<?php
namespace System\Libraries;

class Monitor {

    // Initial time and memory
    private static $startTime = APP_START_TIME;
    private static $startMemory = APP_START_MEMORY;

    // Profiling markers
    protected static $markers = [];

    /**
     * Measure end of entire request from framework startup
     *
     * @return array Result with execution time, memory used, CPU load
     */
    public static function endFramework() {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = $endTime - self::$startTime;
        $memoryUsed = $endMemory - self::$startMemory;

        $cpuUsage = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'Cannot get CPU load!';

        return [
            'execution_time' => $executionTime,
            'memory_used'    => self::formatMemorySize($memoryUsed),
            'cpu_usage'      => $cpuUsage
        ];
    }

    /**
     * Format memory size
     *
     * @param int $size Size
     * @return string Formatted size
     */
    public static function formatMemorySize($size) {
        if ($size < 1024) {
            return $size . ' Bytes';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } elseif ($size < 1073741824) {
            return round($size / 1048576, 2) . ' MB';
        } else {
            return round($size / 1073741824, 2) . ' GB';
        }
    }

    /**
     * (Alias) Format for profiling: use formatMemorySize()
     */
    protected static function formatMemory($bytes) {
        return self::formatMemorySize($bytes);
    }

    /**
     * Mark profiling start point
     *
     * @param string $label Marker label
     */
    public static function mark($label) {
        self::$markers[$label]['start'] = microtime(true);
        self::$markers[$label]['memory_start'] = memory_get_usage();
    }

    /**
     * Stop marked marker and calculate time, memory used
     *
     * @param string $label Marker label
     */
    public static function stop($label) {
        self::$markers[$label]['end'] = microtime(true);
        self::$markers[$label]['memory_end'] = memory_get_usage();
    }

    /**
     * Get list of marked profiles
     *
     * @return array Profile array with label, time and memory usage
     */
    public static function getProfiles() {
        $results = [];
        foreach (self::$markers as $label => $mark) {
            $duration = isset($mark['end']) ? $mark['end'] - $mark['start'] : 0;
            $memoryUsed = isset($mark['memory_end']) ? $mark['memory_end'] - $mark['memory_start'] : 0;
            $results[] = [
                'label'  => $label,
                'time'   => $duration,
                'memory' => self::formatMemory($memoryUsed)
            ];
        }
        return $results;
    }
}
