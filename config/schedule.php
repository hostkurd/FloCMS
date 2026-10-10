<?php
declare(strict_types=1);

use FloCMS\CLI\Scheduling\Schedule;

/*
 * Scheduled tasks, run by one cron job (cPanel: Cron Jobs, "Once Per Minute"):
 *
 *     * * * * * php /home/USER/site/flo schedule:run >> /dev/null 2>&1
 *
 * See them with `php flo schedule:list`; results go to storage/logs/schedule.log.
 */
return static function (Schedule $schedule): void {
    // Built-in housekeeping: api:gc (stale API rate-limit and idempotency
    // state), log rotation, and abandoned chunked uploads in storage/uploads/chunks.
    $schedule->defaults();

    // Your tasks, e.g.:
    // $schedule->command('reports:send')->weekdays()->dailyAt('07:30');
    // $schedule->command('api:gc', ['--database'])->dailyAt('03:15');
};
