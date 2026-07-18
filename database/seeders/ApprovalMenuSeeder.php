<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApprovalMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = DB::table('products')->get();

        foreach ($menus as $menu) {

            $tenant = DB::table('tenants')
                ->where('id', $menu->tenant_id)
                ->first();

            if (!$tenant) continue;

            $approvalTenant = DB::table('approval_tenants')
                ->where('tenant_code', $tenant->tenant_code)
                ->first();

            if (!$approvalTenant) continue;

            DB::table('approval_menus')->insert([
                'tenant_id'   => $approvalTenant->id,
                'category_id' => $menu->category_id,
                'name'        => $menu->name,
                'slug'        => $menu->slug,
                'description' => $menu->description,
                'price'       => $menu->price,
                'product_img' => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
