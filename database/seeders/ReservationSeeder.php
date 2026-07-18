<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UserTenant;
use Carbon\Carbon;
use function Symfony\Component\Clock\now;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = UserTenant::all();
        $index = 1;
        foreach ($users as $user) {
            Reservation::insert([
                'user_id' => $user->id,               
                'start_date' =>Carbon::now()->subDays(7-$index++)->format('Y-m-d'), 
                'end_date' => Carbon::now()->addDays(7+$index++)->format('Y-m-d'),
            ]);
        }
    }
}
