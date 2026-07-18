<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\PickupSlot;
use App\Models\User;
use App\Models\UserTenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Categorie::insert([
        //     [
        //         'name' => 'Makanan',
        //         'slug' => 'makanan',
        //         'description' => 'Kategori makanan',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Minuman',
        //         'slug' => 'minuman',
        //         'description' => 'Kategori minuman',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Snack',
        //         'slug' => 'snack',
        //         'description' => 'Kategori snack',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Dessert',
        //         'slug' => 'dessert',
        //         'description' => 'Kategori dessert',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // ]);

        // User::insert([
        //     [
        //         'name' => 'Andi Saputra',
        //         'email' => 'andi@gmail.com',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('password'),
        //         'phone' => '081234567890',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Budi Santoso',
        //         'email' => 'budi@gmail.com',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('password'),
        //         'phone' => '081234567891',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],

        //     [
        //         'name' => 'Alif Ghazy',
        //         'email' => 'alif@gmail.com',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('password'),
        //         'phone' => '081953312187',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]
        // ]);

        // UserTenant::insert([
        //     [
        //         'nim' => '20241020025',
        //         'name' => 'Citra Dewi',
        //         'prodi' => 'Manajemen',
        //         'semester' => '4',
        //         'email' => 'citra@gmail.com',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('password'),
        //         'phone' => '081234567892',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'nim' => '20231020021',
        //         'name' => 'Dedi Pratama',
        //         'prodi' => 'Bisnis Digital',
        //         'semester' => '6',
        //         'email' => 'dedi@gmail.com',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('password'),
        //         'phone' => '081234567893',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'nim' => '20231020045',
        //         'name' => 'Alif Ghazy',
        //         'prodi' => 'Teknik Informatika',
        //         'semester' => '6',
        //         'email' => 'alif@gmail.com',
        //         'email_verified_at' => now(),
        //         'password' => Hash::make('password'),
        //         'phone' => '081953312187',
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]
        // ]);

        PickupSlot::insert([
            [
                'tenant_id' => 1,
                'dayPickup' => 'Rabu',
                'start_time' => '12:30:00',
                'end_time' => '16:30:00',
                'created_at' =>  now(),
                'updated_at' =>  now(),
            ],
            [
                'tenant_id' => 1,
                'dayPickup' => 'Kamis',
                'start_time' => '12:30:00',
                'end_time' => '16:30:00',
                'created_at' =>  now(),
                'updated_at' =>  now(),
            ],
            // [
            //     'tenant_id' => 2,
            //     'dayPickup' => 'Senin',
            //     'start_time' => '08:30:00',
            //     'end_time' => '11:30:00',
            //     'created_at' =>  now(),
            //     'updated_at' =>  now(),
            // ],
            // [
            //     'tenant_id' => 2,
            //     'dayPickup' => 'Selasa',
            //     'start_time' => '08:30:00',
            //     'end_time' => '11:30:00',
            //     'created_at' =>  now(),
            //     'updated_at' =>  now(),
            // ],
            // [
            //     'tenant_id' => 2,
            //     'dayPickup' => 'Kamis',
            //     'start_time' => '09:30:00',
            //     'end_time' => '10:30:00',
            //     'created_at' =>  now(),
            //     'updated_at' =>  now(),
            // ]
        ]);

        // $this->call([
        //     ReservationSeeder::class,
        //     TenantSeeder::class,
        //     ProductSeeder::class,
        //     ApprovalTenantSeeder::class,
        //     ApprovalMenuSeeder::class,
        // ]);

        
    }
}
