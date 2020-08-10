<?php

namespace App\Console;

use App\Console\Commands\Update;
use App\Jobs\IPToLocation\Fetch;
use App\Jobs\IPToLocation\Unzip;
use App\Jobs\IPToLocation\Insert;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        Update::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new Fetch)->dailyAt("5:00");
        $schedule->job(new Unzip)->dailyAt("6:00");
        $schedule->job(new Insert)->dailyAt("6:00");
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
