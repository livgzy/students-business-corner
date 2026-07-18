<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    Order::where('status', 'Pending')
        ->where('payment_method', 'Non Tunai')
        ->whereNull('payment_proof_img')
        ->where('created_at', '<=', now()->subMinutes(30))
        ->update(['status' => 'Dibatalkan']);
})->everyMinute();