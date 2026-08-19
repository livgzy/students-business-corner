<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\PaymentBatch;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    PaymentBatch::where('status', 'Pending')
        ->where('expired_at', '<=', now())
        ->get()
        ->each(fn (PaymentBatch $batch) => $batch->markAsExpired());
})->everyMinute()->withoutOverlapping();