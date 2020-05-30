<?php

namespace App\Console;

use App\Console\Commands\UpdateBalanceCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        UpdateBalanceCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Update balance for MTN Cashin
        $schedule->command('balance:update', [config('app.services.orange.cashin_code')])->everyTenMinutes();
    }
}
