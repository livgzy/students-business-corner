<?php

namespace App\Console\Commands;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:cancel-expired')]
#[Description('Cancel non-tunai orders that passed the 30 minute payment proof window without any proof uploaded')]
class CancelExpiredOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $cancelled = Order::where('status', 'Pending')
        //     ->where('payment_method', 'Non Tunai')
        //     ->whereNull('payment_proof_img')
        //     ->where('created_at', '<=', now()->subMinutes(3))
        //     ->update(['status' => 'Dibatalkan']);
 
        // $this->info("Cancelled {$cancelled} expired order(s).");
 
        // return self::SUCCESS;
    }
}
