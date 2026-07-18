<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovalTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            DB::table('approval_tenants')->insert([
                'tenant_code'   => $tenant->tenant_code,
                'reservation_id'=> $tenant->reservation_id,
                'store_name'    => $tenant->store_name,
                'slug'          => Str::slug($tenant->store_name),
                'description'   => $tenant->description,
                'phone'         => $tenant->phone,
                'tenant_img'    => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

    }
}
