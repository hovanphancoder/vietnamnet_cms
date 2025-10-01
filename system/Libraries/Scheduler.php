<?php

namespace System\Libraries;

use App\Console\Kernel;

/**
 * Command Scheduler
 * Handles scheduled command execution (similar to CMSFullForm's Task Scheduler)
 */
class Scheduler
{
    private $kernel;
    private $logFile;
    private $schedules;
    private $runningCommands = [];
    private $statusFile;
    private $detailedLogging = false; // Disable detailed logging by default

    public function __construct(?array $schedules = null, bool $detailedLogging = false)
    {
        $this->kernel = new Kernel();
        $this->logFile = PATH_ROOT . '/writeable/logs/scheduler.log';
        $this->statusFile = PATH_ROOT . '/writeable/logs/scheduler_status.json';
        $this->detailedLogging = $detailedLogging;

        if ($schedules === null) {
            $scheduleBuilder = $this->kernel->getScheduleBuilder();
            $schedules = $scheduleBuilder->getCronSchedules();
        }

        $this->schedules = $schedules;
        $this->loadRunningCommands();
    }

    /**
     * Run scheduled commands
     */
    public function run(): void
    {
        $this->log('Scheduler started at ' . date('Y-m-d H:i:s'));

        $now = new \_DateTime();
        $currentTime = $now->format('H:i');
        $currentDay = $now->format('N'); // 1-7 (Monday-Sunday)
        $currentDate = $now->format('Y-m-d');

        // Collect commands that should run (only if not already running)
        $commandsToRun = [];
        foreach ($this->schedules as $command => $cronExpression) {
            if ($this->shouldRun($cronExpression, $currentTime, $currentDay, $currentDate)) {
                // Check if command is already running
                if (!$this->isCommandRunning($command)) {
                    $commandsToRun[] = $command;
                } else {
                    $this->log("Command '$command' is already running, skipping", 'warning');
                }
            }
        }

        if (empty($commandsToRun)) {
            $this->log('No commands scheduled to run at this time');
            return;
        }

        $this->log('Found ' . count($commandsToRun) . ' commands to run: ' . implode(', ', $commandsToRun));

        // Execute commands in parallel if exec is available
        if ($this->isExecAvailable()) {
            $this->executeCommandsInParallel($commandsToRun);
        } else {
            $this->executeCommandsSequentially($commandsToRun);
        }

        $this->log('Scheduler completed at ' . date('Y-m-d H:i:s'));
    }

    /**
     * Check if command should run based on cron expression
     */
    private function shouldRun(string $cronExpression, string $currentTime, int $currentDay, string $currentDate): bool
    {
        $parts = explode(' ', trim($cronExpression));

        if (count($parts) !== 5) {
            return false;
        }

        list($minute, $hour, $day, $month, $dayOfWeek) = $parts;

        // Check if current time matches
        $currentHour = (int) date('H');
        $currentMinute = (int) date('i');

        // Parse hour
        if (!$this->matchesTime($hour, $currentHour)) {
            return false;
        }

        // Parse minute
        if (!$this->matchesTime($minute, $currentMinute)) {
            return false;
        }

        // Parse day of month
        if (!$this->matchesTime($day, (int) date('j'))) {
            return false;
        }

        // Parse month
        if (!$this->matchesTime($month, (int) date('n'))) {
            return false;
        }

        // Parse day of week
        if (!$this->matchesTime($dayOfWeek, $currentDay)) {
            return false;
        }

        return true;
    }

    /**
     * Check if time matches cron expression
     */
    private function matchesTime(string $expression, int $value): bool
    {
        if ($expression === '*') {
            return true;
        }

        // Handle ranges (e.g., 1-5)
        if (strpos($expression, '-') !== false) {
            list($start, $end) = explode('-', $expression);
            return $value >= (int) $start && $value <= (int) $end;
        }

        // Handle lists (e.g., 1,3,5)
        if (strpos($expression, ',') !== false) {
            $values = explode(',', $expression);
            return in_array($value, array_map('intval', $values));
        }

        // Handle intervals (e.g., */2)
        if (strpos($expression, '*/') !== false) {
            $interval = (int) substr($expression, 2);
            return $value % $interval === 0;
        }

        // Exact match
        return (int) $expression === $value;
    }

    /**
     * Execute commands in parallel using exec
     */
    private function executeCommandsInParallel(array $commands): void
    {
        $this->log('Executing commands in PARALLEL mode', 'info');

        $artisanPath = PATH_ROOT . '/artisan';
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // Start all commands in background simultaneously
        $processes = [];

        foreach ($commands as $command) {
            // Mark command as started
            $this->markCommandStarted($command, 600); // 10 minutes max run time

            $commandLine = "php \"$artisanPath\" $command";
            $this->log("Starting parallel command: $command", 'info');

            if ($isWindows) {
                // Windows: use start command with /MIN to run minimized in background
                if ($this->detailedLogging) {
                    $logFile = PATH_ROOT . '/writeable/logs/command_' . $command . '_' . time() . '.log';
                    $windowsCommand = "start /MIN /B $commandLine > \"$logFile\" 2>&1";
                    $this->log("Command '$command' started in background (log: $logFile)", 'success');
                } else {
                    $windowsCommand = "start /MIN /B $commandLine > nul 2>&1";
                    $this->log("Command '$command' started in background (no detailed log)", 'success');
                }
                $process = popen($windowsCommand, 'r');
                if ($process) {
                    $processes[] = $process;
                } else {
                    $this->log("Failed to start command '$command' in background", 'error');
                    $this->markCommandCompleted($command);
                }
            } else {
                // Unix/Linux: use & to run in background
                if ($this->detailedLogging) {
                    $logFile = PATH_ROOT . '/writeable/logs/command_' . $command . '_' . time() . '.log';
                    exec("$commandLine > \"$logFile\" 2>&1 &", $output, $returnCode);
                    $this->log("Command '$command' started in background (log: $logFile)", 'success');
                } else {
                    exec("$commandLine > /dev/null 2>&1 &", $output, $returnCode);
                    $this->log("Command '$command' started in background (no detailed log)", 'success');
                }
                if ($returnCode !== 0) {
                    $this->log("Failed to start command '$command' in background (return code: $returnCode)", 'error');
                    $this->markCommandCompleted($command);
                }
            }
        }

        // Close all process handles
        foreach ($processes as $process) {
            pclose($process);
        }

        $this->log('All commands started in parallel. They will run in background.', 'info');
    }

    /**
     * Execute commands sequentially (fallback)
     */
    private function executeCommandsSequentially(array $commands): void
    {
        $this->log('Executing commands in SEQUENTIAL mode (exec disabled)', 'warning');

        foreach ($commands as $command) {
            // Mark command as started
            $this->markCommandStarted($command, 600); // 10 minutes max run time

            $this->executeCommand($command);

            // Mark command as completed
            $this->markCommandCompleted($command);
        }
    }

    /**
     * Execute a command
     */
    private function executeCommand(string $command): void
    {
        $this->log("Executing scheduled command: $command");

        try {
            // Check if exec is available
            if (!$this->isExecAvailable()) {
                $this->log("exec() function is disabled. Using direct command execution.", 'warning');
                $this->executeCommandDirectly($command);
                return;
            }

            // Build command line
            $artisanPath = PATH_ROOT . '/artisan';
            $commandLine = "php \"$artisanPath\" $command";

            // Execute command using exec
            $output = [];
            $returnCode = 0;
            exec($commandLine, $output, $returnCode);

            if ($returnCode === 0) {
                $this->log("Command '$command' executed successfully");
                if (!empty($output)) {
                    $this->log("Output: " . implode("\n", $output));
                }
            } else {
                $this->log("Command '$command' failed with return code: $returnCode", 'error');
                if (!empty($output)) {
                    $this->log("Error output: " . implode("\n", $output), 'error');
                }
            }
        } catch (\Exception $e) {
            $this->log("Error executing command '$command': " . $e->getMessage(), 'error');
        }
    }

    /**
     * Check if exec function is available
     */
    private function isExecAvailable(): bool
    {
        // Check if exec is in disabled functions
        $disabledFunctions = ini_get('disable_functions');
        if ($disabledFunctions && strpos($disabledFunctions, 'exec') !== false) {
            return false;
        }

        // Check if exec function exists
        if (!function_exists('exec')) {
            return false;
        }

        return true;
    }

    /**
     * Execute command directly without exec (fallback method)
     */
    private function executeCommandDirectly(string $command): void
    {
        try {
            // Parse command and arguments
            $parts = explode(' ', $command);
            $commandName = $parts[0];
            $arguments = array_slice($parts, 1);

            // Get command class from Kernel
            $kernel = new Kernel();
            $commandClass = $kernel->getCommand($commandName);

            if (!$commandClass) {
                $this->log("Command '$commandName' not found", 'error');
                return;
            }

            // Instantiate and execute command
            $commandInstance = new $commandClass();
            $commandInstance->execute($arguments, []);

            $this->log("Command '$command' executed successfully (direct execution)");
        } catch (\Exception $e) {
            $this->log("Error executing command '$command' directly: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;

        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);

        // Also output to console if running interactively
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }

    /**
     * Get scheduler status
     */
    public function getStatus(): array
    {
        $schedule = $this->kernel->getSchedule();
        $status = [];

        foreach ($schedule as $command => $cronExpression) {
            $status[] = [
                'command' => $command,
                'schedule' => $cronExpression,
                'next_run' => $this->getNextRunTime($cronExpression),
                'last_run' => $this->getLastRunTime($command)
            ];
        }

        return $status;
    }

    /**
     * Get next run time for a command
     */
    private function getNextRunTime(string $cronExpression): string
    {
        // Simple implementation - in production you might want to use a proper cron parser
        return 'Next run time calculation not implemented';
    }

    /**
     * Get last run time for a command
     */
    private function getLastRunTime(string $command): string
    {
        // Check log file for last execution
        if (file_exists($this->logFile)) {
            $logContent = file_get_contents($this->logFile);
            $lines = explode("\n", $logContent);

            foreach (array_reverse($lines) as $line) {
                if (strpos($line, "Executing scheduled command: $command") !== false) {
                    preg_match('/\[([^\]]+)\]/', $line, $matches);
                    return $matches[1] ?? 'Unknown';
                }
            }
        }

        return 'Never';
    }

    /**
     * Load running commands from status file
     */
    private function loadRunningCommands(): void
    {
        if (file_exists($this->statusFile)) {
            $data = json_decode(file_get_contents($this->statusFile), true);
            $this->runningCommands = $data ?: [];
        }
    }

    /**
     * Save running commands to status file
     */
    private function saveRunningCommands(): void
    {
        file_put_contents($this->statusFile, json_encode($this->runningCommands, JSON_PRETTY_PRINT));
    }

    /**
     * Check if command is currently running
     */
    private function isCommandRunning(string $command): bool
    {
        if (!isset($this->runningCommands[$command])) {
            return false;
        }

        $commandData = $this->runningCommands[$command];
        $startTime = $commandData['start_time'];
        $maxRunTime = $commandData['max_run_time'] ?? 300; // 5 minutes default

        // Check if command has been running too long (stale process)
        if (time() - $startTime > $maxRunTime) {
            $this->log("Command '$command' has been running too long, marking as completed", 'warning');
            $this->markCommandCompleted($command);
            return false;
        }

        // Also check if it's been running for more than 10 minutes (safety check)
        if (time() - $startTime > 600) {
            $this->log("Command '$command' has been running for more than 10 minutes, marking as completed", 'warning');
            $this->markCommandCompleted($command);
            return false;
        }

        return true;
    }

    /**
     * Mark command as started
     */
    private function markCommandStarted(string $command, int $maxRunTime = 300): void
    {
        $this->runningCommands[$command] = [
            'start_time' => time(),
            'max_run_time' => $maxRunTime,
            'status' => 'running'
        ];
        $this->saveRunningCommands();
    }

    /**
     * Mark command as completed
     */
    private function markCommandCompleted(string $command): void
    {
        unset($this->runningCommands[$command]);
        $this->saveRunningCommands();
    }

    /**
     * Get running commands status
     */
    public function getRunningCommands(): array
    {
        return $this->runningCommands;
    }

    /**
     * Set detailed logging
     */
    public function setDetailedLogging(bool $enabled): void
    {
        $this->detailedLogging = $enabled;
    }

    /**
     * Get detailed logging status
     */
    public function isDetailedLoggingEnabled(): bool
    {
        return $this->detailedLogging;
    }
}
