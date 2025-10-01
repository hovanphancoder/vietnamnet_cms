<?php

namespace App\Console;

use System\Commands\MakeCommand;
use System\Libraries\ScheduleBuilder;

/**
 * Console Kernel
 * Manages console commands and scheduling (CMSFullForm-style)
 */
class Kernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        // Make Commands
        MakeCommand::class,

        // Schedule Commands
        \App\Console\System\ScheduleRunCommand::class,
        \App\Console\System\ScheduleCronCommand::class,
        \App\Console\System\ScheduleWorkCommand::class,

        // Main Commands
        \App\Console\Commands\GoldCommand::class,
        \App\Console\Commands\CleanupCommand::class,
        \App\Console\Commands\LogsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule($schedule)
    {
        // Gold commands
        $schedule->command('gold')->cron('* * * * *');
        // OR $schedule->command('gold')->everyFiveMinutes();
        $schedule->command('gold categories')->dailyAt('00:00');
        $schedule->command('gold fill-missing')->dailyAt('00:00');
    }

    /**
     * Get the schedule builder instance
     */
    public function getScheduleBuilder(): ScheduleBuilder
    {
        $schedule = new ScheduleBuilder();
        $this->schedule($schedule);
        return $schedule;
    }

    /**
     * Get all registered commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get command schedule
     */
    public function getSchedule(): array
    {
        $scheduleBuilder = $this->getScheduleBuilder();
        return $scheduleBuilder->getCronSchedules();
    }

    /**
     * Register a new command
     */
    public function registerCommand(string $command): void
    {
        if (!in_array($command, $this->commands)) {
            $this->commands[] = $command;
        }
    }

    /**
     * Get command by name
     */
    public function getCommand(string $name): ?string
    {
        foreach ($this->commands as $command) {
            $commandInstance = new $command();
            if ($commandInstance->getName() === $name) {
                return $command;
            }
        }
        return null;
    }

    /**
     * Get commands by category
     */
    public function getCommandsByCategory(string $category): array
    {
        $filteredCommands = [];

        foreach ($this->commands as $command) {
            $commandInstance = new $command();
            $commandName = $commandInstance->getName();

            if (strpos($commandName, $category . ':') === 0) {
                $filteredCommands[] = $command;
            }
        }

        return $filteredCommands;
    }

    /**
     * Get all command categories
     */
    public function getCategories(): array
    {
        $categories = [];

        foreach ($this->commands as $command) {
            $commandInstance = new $command();
            $commandName = $commandInstance->getName();
            $category = explode(':', $commandName)[0];

            if (!in_array($category, $categories)) {
                $categories[] = $category;
            }
        }

        return $categories;
    }
}
