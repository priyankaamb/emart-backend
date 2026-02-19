<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coupon;
use Carbon\Carbon;
class DeactivateExpiredCoupon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deactivate-expired-coupon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate coupon that have expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Cron Job running at coupon " . now());
        $currentDateTime = convertToIST(Carbon::now('Asia/Kolkata'));
        info("coupon: " . $currentDateTime);

        $coupon = Coupon::where('end_date', '<', $currentDateTime)
                        ->where('is_active', 1)
                        ->first();

        if ($coupon) {
            $coupon->update(['is_active' => 0]);
            $this->info("Coupon with code {$coupon->code} has been deactivated.");
        } else {
            $this->info('No expired coupons to deactivate.');
        }
    }
}
