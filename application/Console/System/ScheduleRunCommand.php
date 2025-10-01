<?php

namespace App\Console\System;

use System\Core\BaseCommand;
use System\Libraries\Scheduler;

/**
 * Schedule Run Command
 * Run scheduled commands
 */
class ScheduleRunCommand extends BaseCommand
{
    protected function initialize(): void
    {
        $this->name = 'schedule:run';
        $this->description = 'Run scheduled commands';

        $this->options = [
            '--daemon' => 'Run in daemon mode (continuous)',
            '--once' => 'Run once and exit',
            '--status' => 'Show scheduler status',
            '--running' => 'Show currently running commands',
            '--detailed-log' => 'Enable detailed logging for each command',
            '--no-detailed-log' => 'Disable detailed logging for each command'
        ];
    }

    public function execute(array $arguments = [], array $options = []): void
    {
        // Determine detailed logging setting
        $detailedLogging = false; // Default: no detailed logging
        if (in_array('--detailed-log', $options)) {
            $detailedLogging = true;
        } elseif (in_array('--no-detailed-log', $options)) {
            $detailedLogging = false;
        }

        $scheduler = new Scheduler(null, $detailedLogging);

        // Check for status option
        if (in_array('--status', $options)) {
            $this->showStatus($scheduler);
            return;
        }

        // Check for running commands option
        if (in_array('--running', $options)) {
            $this->showRunningCommands($scheduler);
            return;
        }

        // Check for daemon mode
        if (in_array('--daemon', $options)) {
            $this->runDaemon($scheduler);
            return;
        }

        // Run once
        $this->runOnce($scheduler);
    }

    /**
     * Run scheduler once
     */
    private function runOnce(Scheduler $scheduler): void
    {
        $this->output('Running scheduled commands...');

        try {
            $scheduler->run();
            $this->output('Scheduled commands completed!');
        } catch (\Exception $e) {
            $this->logError('Error running scheduler', $e);
        }
    }

    /**
     * Run scheduler in daemon mode
     */
    private function runDaemon(Scheduler $scheduler): void
    {
        $this->output('Starting scheduler daemon...');
        $this->output('Press Ctrl+C to stop');

        while (true) {
            try {
                $scheduler->run();
                sleep(60); // Check every minute
            } catch (\Exception $e) {
                $this->logError('Error in daemon mode', $e);
                sleep(60);
            }
        }
    }

    /**
     * Show scheduler status
     */
    private function showStatus(Scheduler $scheduler): void
    {
        $this->output('Scheduler Status');
        $this->output('================');

        $status = $scheduler->getStatus();

        foreach ($status as $item) {
            $this->output("Command: {$item['command']}");
            $this->output("  Schedule: {$item['schedule']}");
            $this->output("  Last Run: {$item['last_run']}");
            $this->output("  Next Run: {$item['next_run']}");
            $this->output('');
        }
    }

    /**
     * Show currently running commands
     */
    private function showRunningCommands(Scheduler $scheduler): void
    {
        $this->output('Currently Running Commands');
        $this->output('============================');

        $runningCommands = $scheduler->getRunningCommands();

        if (empty($runningCommands)) {
            $this->output('No commands are currently running.');
            return;
        }

        foreach ($runningCommands as $command => $data) {
            $startTime = date('Y-m-d H:i:s', $data['start_time']);
            $runningTime = time() - $data['start_time'];
            $maxTime = $data['max_run_time'];

            $this->output("Command: $command");
            $this->output("  Started: $startTime");
            $this->output("  Running for: {$runningTime}s");
            $this->output("  Max time: {$maxTime}s");
            $this->output("  Status: {$data['status']}");
            $this->output('');
        }
    }
}
