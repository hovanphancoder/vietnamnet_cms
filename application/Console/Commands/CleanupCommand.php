<?php

namespace App\Console\Commands;

use System\Core\BaseCommand;

/**
 * Cleanup Command
 * Clean up log files and temporary data
 */
class CleanupCommand extends BaseCommand
{
    protected function initialize(): void
    {
        $this->name = 'cleanup';
        $this->description = 'Clean up log files and temporary data';

        $this->options = [
            '--logs' => 'Clean up log files only',
            '--all' => 'Clean up all temporary data',
            '--force' => 'Force cleanup without confirmation'
        ];
    }

    public function execute(array $arguments = [], array $options = []): void
    {
        $cleanLogs = in_array('--logs', $options);
        $cleanAll = in_array('--all', $options);
        $force = in_array('--force', $options);


        if (!$cleanLogs && !$cleanAll) {
            $this->output("Please specify what to clean up:");
            $this->output("  --logs    Clean up log files");
            $this->output("  --all     Clean up all temporary data");
            $this->output("");
            $this->output("Examples:");
            $this->output("  php fast cleanup --logs");
            $this->output("  php fast cleanup --logs --force");
            return;
        }

        if ($cleanLogs || $cleanAll) {
            $this->cleanupLogFiles($force);
        }
    }

    /**
     * Clean up log files
     */
    private function cleanupLogFiles(bool $force = false): void
    {
        $this->output("🧹 Cleaning up log files...");

        $logsDir = PATH_ROOT . '/writeable/logs';
        $commandLogs = glob($logsDir . '/command_*.log');
        $debugLogs = glob($logsDir . '/debug_*.log');

        $totalFiles = count($commandLogs) + count($debugLogs);

        if ($totalFiles === 0) {
            $this->output("✅ No log files to clean up.");
            return;
        }

        if (!$force) {
            $this->output("Found $totalFiles log files to clean up:");
            $this->output("  - Command logs: " . count($commandLogs));
            $this->output("  - Debug logs: " . count($debugLogs));

            if (!$this->confirm("Do you want to delete these files?")) {
                $this->output("Cleanup cancelled.");
                return;
            }
        }

        $deletedCount = 0;
        $errorCount = 0;

        // Delete command logs
        foreach ($commandLogs as $file) {
            if (unlink($file)) {
                $deletedCount++;
            } else {
                $errorCount++;
            }
        }

        // Delete debug logs
        foreach ($debugLogs as $file) {
            if (unlink($file)) {
                $deletedCount++;
            } else {
                $errorCount++;
            }
        }

        $this->output("📊 Cleanup results:");
        $this->output("✅ Deleted: $deletedCount files");

        if ($errorCount > 0) {
            $this->output("❌ Failed: $errorCount files (may be in use)");
            $this->output("💡 Tip: Stop scheduler before cleanup to avoid file locks");
        }

        $this->output("🎉 Log cleanup completed!");
    }
}
