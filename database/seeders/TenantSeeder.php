<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;


class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reservations = Reservation::all()->load("user");
        $index = 0; 
        foreach ($reservations as $reservation) {
            Tenant::factory()->create([
                'tenant_code' => chr(65 + $index++), 
                'reservation_id' => $reservation->id,               
                'store_name' => $reservation->user->name . ' Store', 
                'slug' => Str::slug($reservation->user->name . ' Store'), 
                'phone' => $reservation->user->phone,
            ]);
        }
    }
}
