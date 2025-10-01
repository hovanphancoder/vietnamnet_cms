<?php

namespace App\Console\System;

use App\Console\Kernel;
use System\Core\BaseCommand;

/**
 * Schedule Cron Command
 * Generate cron job entries for scheduled commands
 */
class ScheduleCronCommand extends BaseCommand
{
    protected function initialize(): void
    {
        $this->name = 'schedule:cron';
        $this->description = 'Generate cron job entries for scheduled commands';

        $this->options = [
            '--user' => 'System user to run cron job (default: current user)',
            '--output' => 'Output file for cron entries (default: crontab.txt)'
        ];
    }

    public function execute(array $arguments = [], array $options = []): void
    {
        $user = $this->getOptionValue('--user', $options, get_current_user());
        $outputFile = $this->getOptionValue('--output', $options, 'crontab.txt');

        $this->output('Generating cron job entries...');

        $cronEntries = $this->generateCronEntries($user);

        // Save to file
        file_put_contents($outputFile, $cronEntries);

        $this->output("Cron entries saved to: $outputFile");
        $this->output('');
        $this->output('To install cron job:');
        $this->output("crontab $outputFile");
        $this->output('');
        $this->output('To view current cron jobs:');
        $this->output('crontab -l');
    }

    /**
     * Generate cron entries
     */
    private function generateCronEntries(string $user): string
    {
        $kernel = new Kernel();
        $schedule = $kernel->getSchedule();

        $entries = [];
        $entries[] = "# CMSFullForm Artisan Scheduler";
        $entries[] = "# Generated on " . date('Y-m-d H:i:s');
        $entries[] = "# User: $user";
        $entries[] = "";

        foreach ($schedule as $command => $cronExpression) {
            $artisanPath = PATH_ROOT . '/artisan';
            $logFile = PATH_ROOT . '/writeable/logs/scheduler.log';

            $entries[] = "# $command";
            $entries[] = "$cronExpression $user cd " . PATH_ROOT . " && php fast $command >> $logFile 2>&1";
            $entries[] = "";
        }

        return implode("\n", $entries);
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
