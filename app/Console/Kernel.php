<?php

namespace App\Console;

use App\Bet;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
        	   $unlocked = Bet::all()->where('locked', false);
        	   
        	   foreach ($unlocked as $bet) {
	            $diff = date_diff(new \DateTime($bet->created_at), new \DateTime());
	            
	            if ($diff->h >= 1) {
	            	$bet->update(['locked' => true]);
	            }
            }
        })->everyMinute();
    }
}
