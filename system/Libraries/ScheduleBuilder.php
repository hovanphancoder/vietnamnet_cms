<?php

namespace System\Libraries;

/**
 * Schedule Builder
 * CMSFullForm-style schedule builder for defining command schedules
 */
class ScheduleBuilder
{
    protected $schedules = [];

    /**
     * Schedule a command to run every five minutes
     */
    public function command(string $command)
    {
        $schedule = new ScheduleItem($command);
        $this->schedules[] = $schedule;
        return $schedule;
    }

    /**
     * Get all scheduled items
     */
    public function getSchedules(): array
    {
        return $this->schedules;
    }

    /**
     * Get schedules as cron expressions
     */
    public function getCronSchedules(): array
    {
        $cronSchedules = [];

        foreach ($this->schedules as $schedule) {
            $cronSchedules[$schedule->getCommand()] = $schedule->getCronExpression();
        }

        return $cronSchedules;
    }
}

/**
 * Schedule Item
 * Represents a single scheduled command
 */
class ScheduleItem
{
    protected $command;
    protected $cronExpression;
    protected $description;

    public function __construct(string $command)
    {
        $this->command = $command;
        $this->cronExpression = '* * * * *'; // Default: every minute
    }

    /**
     * Run every five minutes
     */
    public function everyFiveMinutes()
    {
        $this->cronExpression = '*/5 * * * *';
        return $this;
    }

    /**
     * Run every thirty minutes
     */
    public function everyThirtyMinutes()
    {
        $this->cronExpression = '*/30 * * * *';
        return $this;
    }

    /**
     * Run every two hours
     */
    public function everyTwoHours()
    {
        $this->cronExpression = '0 */2 * * *';
        return $this;
    }

    /**
     * Run every four hours
     */
    public function everyFourHours()
    {
        $this->cronExpression = '0 */4 * * *';
        return $this;
    }

    /**
     * Run daily at specific time
     */
    public function dailyAt(string $time)
    {
        $this->cronExpression = $this->timeToCron($time);
        return $this;
    }

    /**
     * Run hourly
     */
    public function hourly()
    {
        $this->cronExpression = '0 * * * *';
        return $this;
    }

    /**
     * Run daily
     */
    public function daily()
    {
        $this->cronExpression = '0 0 * * *';
        return $this;
    }

    /**
     * Run weekly
     */
    public function weekly()
    {
        $this->cronExpression = '0 0 * * 0';
        return $this;
    }

    /**
     * Run monthly
     */
    public function monthly()
    {
        $this->cronExpression = '0 0 1 * *';
        return $this;
    }

    /**
     * Set custom cron expression
     */
    public function cron(string $expression)
    {
        $this->cronExpression = $expression;
        return $this;
    }

    /**
     * Set description
     */
    public function description(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get command
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * Get cron expression
     */
    public function getCronExpression(): string
    {
        return $this->cronExpression;
    }

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Convert time string to cron expression
     */
    private function timeToCron(string $time): string
    {
        $parts = explode(':', $time);
        $hour = $parts[0];
        $minute = $parts[1] ?? '0';

        return "$minute $hour * * *";
    }
}
