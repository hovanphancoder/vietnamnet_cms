<?php

namespace App\Console\System;

use System\Core\BaseCommand;
use System\Libraries\Scheduler;

/**
 * Schedule Work Command
 * Run the scheduler in daemon mode (CMSFullForm-style)
 */
class ScheduleWorkCommand extends BaseCommand
{
    protected function initialize(): void
    {
        $this->name = 'schedule:work';
        $this->description = 'Start the schedule worker to run scheduled commands';

        $this->options = [
            '--timeout' => 'The number of seconds a child process can run (default: 60)',
            '--memory' => 'The memory limit in megabytes (default: 128)',
            '--sleep' => 'Number of seconds to sleep when no commands are ready (default: 60)',
            '--tries' => 'Number of times to attempt a command when it fails (default: 3)',
            '--max-time' => 'The maximum number of seconds the worker should run (default: 0 = unlimited)',
            '--detailed-log' => 'Enable detailed logging for each command',
            '--no-detailed-log' => 'Disable detailed logging for each command'
        ];
    }

    public function execute(array $arguments = [], array $options = []): void
    {
        $timeout = (int) ($this->getOptionValue('--timeout', $options, 60));
        $memory = (int) ($this->getOptionValue('--memory', $options, 128));
        $sleep = (int) ($this->getOptionValue('--sleep', $options, 60));
        $tries = (int) ($this->getOptionValue('--tries', $options, 3));
        $maxTime = (int) ($this->getOptionValue('--max-time', $options, 0));

        // Determine detailed logging setting
        $detailedLogging = false; // Default: no detailed logging
        if (in_array('--detailed-log', $options)) {
            $detailedLogging = true;
        } elseif (in_array('--no-detailed-log', $options)) {
            $detailedLogging = false;
        }

        $this->output("Starting schedule worker...");
        $this->output("Timeout: {$timeout}s, Memory: {$memory}MB, Sleep: {$sleep}s");
        $this->output("Detailed logging: " . ($detailedLogging ? 'Enabled' : 'Disabled'));
        $this->output("Press Ctrl+C to stop the worker");
        $this->output("");

        try {
            $scheduler = new Scheduler(null, $detailedLogging);

            $startTime = time();
            $lastRun = 0;

            while (true) {
                $currentTime = time();

                // Check if max time reached
                if ($maxTime > 0 && ($currentTime - $startTime) >= $maxTime) {
                    $this->output("Maximum execution time reached. Stopping worker.");
                    break;
                }

                // Run scheduler every minute
                if (($currentTime - $lastRun) >= 60) {
                    $this->output("[" . date('Y-m-d H:i:s') . "] Running scheduled commands...");

                    $scheduler->run();
                    $lastRun = $currentTime;
                }

                // Sleep for 1 second to prevent high CPU usage
                sleep(1);
            }
        } catch (\Exception $e) {
            $this->logError("Schedule worker error", $e);
        }
    }

    /**
     * Show help information
     */
    public function showHelp(): void
    {
        $this->output("Schedule Work Command - Run scheduled commands in daemon mode");
        $this->output("");
        $this->output("Usage:");
        $this->output("  php fast schedule:work [options]");
        $this->output("");
        $this->output("Options:");
        $this->output("  --timeout=60     The number of seconds a child process can run");
        $this->output("  --memory=128     The memory limit in megabytes");
        $this->output("  --sleep=60       Number of seconds to sleep when no commands are ready");
        $this->output("  --tries=3        Number of times to attempt a command when it fails");
        $this->output("  --max-time=0     The maximum number of seconds the worker should run");
        $this->output("  --detailed-log   Enable detailed logging for each command");
        $this->output("  --no-detailed-log Disable detailed logging for each command");
        $this->output("");
        $this->output("Examples:");
        $this->output("  php fast schedule:work");
        $this->output("  php fast schedule:work --timeout=120 --memory=256");
        $this->output("  php fast schedule:work --max-time=3600");
    }

    /**
     * Get option value from command line options
     */
    private function getOptionValue(string $option, array $options, $default = null)
    {
        foreach ($options as $opt) {
            if (strpos($opt, $option . '=') === 0) {
                return substr($opt, strlen($option) + 1);
            }
        }
        return $default;
    }
}
