<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Deal;
class DeactivateExpiredDeals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:deactivate-expired-deals';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';

    /**
     * Execute the console command.
     */

     protected $signature = 'deals:deactivate-expired';
     protected $description = 'Deactivate deals that have expired';
 

    public function handle()
    {
        info("Cron Job running at ". now());
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));
        info("Converted date and time: " . $currentDateTime);
        $expiredDeals = Deal::where('end_date', '<', $currentDateTime)
                            ->where('is_active', 1)
                            ->get();

        foreach ($expiredDeals as $deal) {
            info("check sheduler work in everyminute" . $currentDateTime);
            $deal->update(['is_active' => 0]);
            $this->info("Deactivated deal: {$deal->name}");
            info("Deactivated deal: {$deal->name} ");
        }
    }
}
